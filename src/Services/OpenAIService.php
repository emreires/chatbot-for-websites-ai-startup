<?php

namespace App\Services;

class OpenAIService {
    private $apiKey;
    private $model;
    private $client;

    public function __construct() {
        $this->apiKey = $_ENV['OPENAI_API_KEY'];
        $this->model = $_ENV['OPENAI_MODEL'];
        $this->client = new \GuzzleHttp\Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ]
        ]);
    }

    public function generateResponse($message, $context, $history) {
        $systemPrompt = $this->buildSystemPrompt($context);
        $messages = $this->formatConversationHistory($history);
        
        // Add user's current message
        $messages[] = [
            'role' => 'user',
            'content' => $message
        ];

        try {
            $response = $this->client->post('chat/completions', [
                'json' => [
                    'model' => $this->model,
                    'messages' => array_merge([
                        ['role' => 'system', 'content' => $systemPrompt]
                    ], $messages),
                    'temperature' => 0.7,
                    'max_tokens' => 500,
                    'frequency_penalty' => 0.5,
                    'presence_penalty' => 0.5,
                ]
            ]);

            $result = json_decode($response->getBody(), true);
            return $result['choices'][0]['message']['content'];

        } catch (\Exception $e) {
            throw new \Exception('Failed to generate response: ' . $e->getMessage());
        }
    }

    public function generateEmbedding($text) {
        try {
            $response = $this->client->post('embeddings', [
                'json' => [
                    'model' => 'text-embedding-3-small',
                    'input' => $text
                ]
            ]);

            $result = json_decode($response->getBody(), true);
            return $result['data'][0]['embedding'];

        } catch (\Exception $e) {
            throw new \Exception('Failed to generate embedding: ' . $e->getMessage());
        }
    }

    private function buildSystemPrompt($context) {
        return "You are a helpful AI assistant for this website. Use the following information to answer questions:\n\n" .
               $context . "\n\n" .
               "If you don't find relevant information in the context, politely say so and suggest contacting human support. " .
               "Keep responses concise and friendly. Don't mention that you're using specific context or that you're an AI unless asked.";
    }

    private function formatConversationHistory($history) {
        $messages = [];
        foreach ($history as $message) {
            $messages[] = [
                'role' => $message['role'],
                'content' => $message['content']
            ];
        }
        return $messages;
    }
} 