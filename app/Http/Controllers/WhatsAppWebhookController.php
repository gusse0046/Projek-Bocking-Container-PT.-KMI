<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    /**
     * Verify webhook
     */
    public function verify(Request $request)
    {
        $verifyToken = config('services.whatsapp.webhook_verify_token');
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            Log::info('WhatsApp webhook verified successfully');
            return response($challenge, 200);
        }

        Log::warning('WhatsApp webhook verification failed', [
            'mode' => $mode,
            'token' => $token
        ]);
        
        return response('Forbidden', 403);
    }

    /**
     * Handle incoming messages
     */
    public function webhook(Request $request)
    {
        try {
            $body = $request->getContent();
            $data = json_decode($body, true);

            Log::info('WhatsApp webhook received', [
                'data' => $data
            ]);

            // Process message status updates
            if (isset($data['entry'])) {
                foreach ($data['entry'] as $entry) {
                    if (isset($entry['changes'])) {
                        foreach ($entry['changes'] as $change) {
                            if ($change['field'] === 'messages') {
                                $this->processMessageUpdates($change['value']);
                            }
                        }
                    }
                }
            }

            return response('OK', 200);

        } catch (\Exception $e) {
            Log::error('WhatsApp webhook error', [
                'error' => $e->getMessage(),
                'body' => $request->getContent()
            ]);

            return response('Error', 500);
        }
    }

    private function processMessageUpdates($value)
    {
        // Process message statuses (sent, delivered, read)
        if (isset($value['statuses'])) {
            foreach ($value['statuses'] as $status) {
                Log::info('WhatsApp message status update', [
                    'message_id' => $status['id'],
                    'status' => $status['status'],
                    'timestamp' => $status['timestamp'],
                    'recipient_id' => $status['recipient_id']
                ]);
            }
        }

        // Process incoming messages (if any)
        if (isset($value['messages'])) {
            foreach ($value['messages'] as $message) {
                Log::info('WhatsApp incoming message', [
                    'from' => $message['from'],
                    'message_id' => $message['id'],
                    'type' => $message['type'],
                    'timestamp' => $message['timestamp']
                ]);
                
                // Handle incoming messages if needed
                // For now, just log them
            }
        }
    }
}