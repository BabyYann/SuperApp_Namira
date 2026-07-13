<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Modules\Yayasan\Models\WhatsAppQueue;
use App\Helpers\WhatsAppHelper;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ProcessWhatsAppQueue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'waha:process-queue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process WAHA WhatsApp messages queue sequentially with delays and backoffs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Starting WhatsApp Anti-Spam Queue Processor...");
        $this->info("Listening for pending messages (FIFO)...");

        while (true) {
            // Check global queue status (default to active)
            $status = Cache::get('waha_queue_status', 'active');
            if ($status === 'paused') {
                $this->comment("Queue processor is PAUSED. Waiting 3 seconds...");
                sleep(3);
                continue;
            }

            // Fetch the next pending message
            $msg = WhatsAppQueue::where('status', 'pending')
                ->where(function($q) {
                    $q->whereNull('next_attempt_at')
                      ->orWhere('next_attempt_at', '<=', now());
                })
                ->orderBy('id', 'asc')
                ->first();

            if (!$msg) {
                // No messages to process, sleep 1.5 seconds to save resources
                usleep(1500000);
                continue;
            }

            $this->info("Processing Message ID: {$msg->id} -> To: {$msg->phone}");
            
            // Mark as sending
            $msg->update([
                'status' => 'sending',
                'last_attempt_at' => now(),
            ]);

            // 1. Apply Random Human Delay (400 - 900 ms)
            $delayMs = random_int(400, 900);
            $this->comment("Applying human-like delay: {$delayMs}ms...");
            usleep($delayMs * 1000);

            // 2. Call WAHA API
            try {
                $response = WhatsAppHelper::sendDirectly($msg->phone, $msg->message);
                $responseBody = $response->body();

                if ($response->successful()) {
                    $this->info("SUCCESS: Message ID {$msg->id} sent to {$msg->phone}!");
                    $msg->update([
                        'status' => 'sent',
                        'error_message' => null,
                    ]);
                } else {
                    $error = "HTTP {$response->status()}: " . substr($responseBody, 0, 150);
                    $this->error("FAIL: Message ID {$msg->id} - {$error}");
                    $this->handleFailure($msg, $error);
                }
            } catch (\Exception $e) {
                $error = "Exception: " . $e->getMessage();
                $this->error("FAIL: Message ID {$msg->id} - {$error}");
                $this->handleFailure($msg, $error);
            }
        }
    }

    /**
     * Handle message sending failure with Exponential Backoff retry.
     */
    private function handleFailure(WhatsAppQueue $msg, $error)
    {
        $newRetryCount = $msg->retry_count + 1;
        
        if ($newRetryCount < 3) {
            // Exponential backoff: delay next attempt by 2^retry minutes (2, 4 minutes)
            $delayMinutes = pow(2, $newRetryCount);
            $nextAttempt = now()->addMinutes($delayMinutes);
            
            $this->comment("Scheduling retry #{$newRetryCount} in {$delayMinutes} minutes (at {$nextAttempt->toTimeString()}) due to temporary error.");
            
            $msg->update([
                'status' => 'pending',
                'retry_count' => $newRetryCount,
                'next_attempt_at' => $nextAttempt,
                'error_message' => $error,
            ]);
        } else {
            $this->error("MAX RETRIES REACHED: Message ID {$msg->id} marked as FAILED.");
            $msg->update([
                'status' => 'failed',
                'retry_count' => $newRetryCount,
                'error_message' => $error,
            ]);
        }
    }
}
