<?php

namespace App\Services;

use App\Config\Database;

class VectorDBService {
    private $db;
    private $openai;
    private $redis;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->openai = new OpenAIService();
        $this->redis = new \Predis\Client([
            'scheme' => 'tcp',
            'host' => $_ENV['REDIS_HOST'],
            'port' => $_ENV['REDIS_PORT'],
            'password' => $_ENV['REDIS_PASSWORD']
        ]);
    }

    public function storePageContent($websiteId, $url, $content) {
        try {
            // Generate embedding for the content
            $embedding = $this->openai->generateEmbedding($content);
            
            // Store in vector database (using Redis as temporary storage)
            $embeddingId = 'emb:' . md5($url);
            $this->redis->set($embeddingId, json_encode($embedding));

            // Update pages table
            $sql = "INSERT INTO pages (website_id, url, content, embedding_id) 
                    VALUES (:website_id, :url, :content, :embedding_id)
                    ON DUPLICATE KEY UPDATE 
                    content = :content,
                    embedding_id = :embedding_id,
                    last_crawled = CURRENT_TIMESTAMP";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'website_id' => $websiteId,
                'url' => $url,
                'content' => $content,
                'embedding_id' => $embeddingId
            ]);

            return true;

        } catch (\Exception $e) {
            throw new \Exception('Failed to store page content: ' . $e->getMessage());
        }
    }

    public function searchSimilar($websiteId, $query, $limit = 5) {
        try {
            // Generate embedding for the query
            $queryEmbedding = $this->openai->generateEmbedding($query);

            // Get all page embeddings for the website
            $pages = $this->getWebsitePages($websiteId);
            
            // Calculate similarities and sort
            $similarities = [];
            foreach ($pages as $page) {
                $pageEmbedding = json_decode($this->redis->get($page['embedding_id']), true);
                if ($pageEmbedding) {
                    $similarity = $this->cosineSimilarity($queryEmbedding, $pageEmbedding);
                    $similarities[] = [
                        'content' => $page['content'],
                        'similarity' => $similarity
                    ];
                }
            }

            // Sort by similarity (highest first)
            usort($similarities, function($a, $b) {
                return $b['similarity'] <=> $a['similarity'];
            });

            // Return top N most similar contents
            $results = array_slice($similarities, 0, $limit);
            
            // Combine contents with similarity scores above threshold
            $relevantContent = '';
            foreach ($results as $result) {
                if ($result['similarity'] > 0.7) { // Threshold for relevance
                    $relevantContent .= $result['content'] . "\n\n";
                }
            }

            return $relevantContent;

        } catch (\Exception $e) {
            throw new \Exception('Failed to search similar content: ' . $e->getMessage());
        }
    }

    private function getWebsitePages($websiteId) {
        $sql = "SELECT content, embedding_id FROM pages WHERE website_id = :website_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['website_id' => $websiteId]);
        return $stmt->fetchAll();
    }

    private function cosineSimilarity($vec1, $vec2) {
        $dot = 0.0;
        $norm1 = 0.0;
        $norm2 = 0.0;

        for ($i = 0; $i < count($vec1); $i++) {
            $dot += $vec1[$i] * $vec2[$i];
            $norm1 += $vec1[$i] * $vec1[$i];
            $norm2 += $vec2[$i] * $vec2[$i];
        }

        $norm1 = sqrt($norm1);
        $norm2 = sqrt($norm2);

        if ($norm1 == 0.0 || $norm2 == 0.0) {
            return 0.0;
        }

        return $dot / ($norm1 * $norm2);
    }
} 