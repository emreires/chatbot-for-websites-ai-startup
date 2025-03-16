<?php

namespace App\Controllers;

use App\Models\User;
use App\Services\OpenAIService;
use App\Services\VectorDBService;

class ChatbotController {
    private $user;
    private $openai;
    private $vectorDb;

    public function __construct() {
        $this->user = new User();
        $this->openai = new OpenAIService();
        $this->vectorDb = new VectorDBService();
    }

    public function handleMessage($websiteId, $visitorId, $message) {
        try {
            // Get website owner's API key and plan
            $website = $this->getWebsiteInfo($websiteId);
            if (!$website) {
                throw new \Exception('Website not found');
            }

            // Check API usage limits
            $this->checkApiLimits($website['user_id']);

            // Get relevant context from vector database
            $context = $this->vectorDb->searchSimilar($websiteId, $message);

            // Prepare conversation history
            $history = $this->getConversationHistory($websiteId, $visitorId);

            // Generate response using OpenAI
            $response = $this->openai->generateResponse($message, $context, $history);

            // Save conversation
            $this->saveConversation($websiteId, $visitorId, $message, $response);

            return [
                'status' => 'success',
                'response' => $response
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    private function getWebsiteInfo($websiteId) {
        $sql = "SELECT w.*, u.api_key, u.plan_type 
                FROM websites w 
                JOIN users u ON w.user_id = u.id 
                WHERE w.id = :website_id";
        
        $db = \App\Config\Database::getInstance()->getConnection();
        $stmt = $db->prepare($sql);
        $stmt->execute(['website_id' => $websiteId]);
        return $stmt->fetch();
    }

    private function checkApiLimits($userId) {
        $usage = $this->user->getApiUsage($userId);
        $limits = [
            'small' => 1000,
            'medium' => 5000,
            'big' => 20000,
            'enterprise' => 100000
        ];

        if ($usage >= $limits[$this->user->getPlanType($userId)]) {
            throw new \Exception('API limit exceeded for current plan');
        }
    }

    private function getConversationHistory($websiteId, $visitorId) {
        $sql = "SELECT m.role, m.content 
                FROM conversations c 
                JOIN messages m ON c.id = m.conversation_id 
                WHERE c.website_id = :website_id 
                AND c.visitor_id = :visitor_id 
                ORDER BY m.created_at DESC 
                LIMIT 10";

        $db = \App\Config\Database::getInstance()->getConnection();
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'website_id' => $websiteId,
            'visitor_id' => $visitorId
        ]);
        return $stmt->fetchAll();
    }

    private function saveConversation($websiteId, $visitorId, $userMessage, $botResponse) {
        $db = \App\Config\Database::getInstance()->getConnection();
        
        // Get or create conversation
        $sql = "INSERT INTO conversations (website_id, visitor_id) 
                SELECT :website_id, :visitor_id 
                WHERE NOT EXISTS (
                    SELECT 1 FROM conversations 
                    WHERE website_id = :website_id 
                    AND visitor_id = :visitor_id
                )";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'website_id' => $websiteId,
            'visitor_id' => $visitorId
        ]);

        $conversationId = $db->lastInsertId() ?: $this->getConversationId($websiteId, $visitorId);

        // Save messages
        $sql = "INSERT INTO messages (conversation_id, role, content) VALUES 
                (:conversation_id, :role, :content)";
        
        $stmt = $db->prepare($sql);
        
        // Save user message
        $stmt->execute([
            'conversation_id' => $conversationId,
            'role' => 'user',
            'content' => $userMessage
        ]);

        // Save bot response
        $stmt->execute([
            'conversation_id' => $conversationId,
            'role' => 'assistant',
            'content' => $botResponse
        ]);
    }

    private function getConversationId($websiteId, $visitorId) {
        $sql = "SELECT id FROM conversations 
                WHERE website_id = :website_id 
                AND visitor_id = :visitor_id 
                LIMIT 1";

        $db = \App\Config\Database::getInstance()->getConnection();
        $stmt = $db->prepare($sql);
        $stmt->execute([
            'website_id' => $websiteId,
            'visitor_id' => $visitorId
        ]);
        return $stmt->fetch()['id'];
    }
} 