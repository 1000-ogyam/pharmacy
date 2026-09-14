<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Models\SmsTemplate;
use App\Services\Sms\QueuedSmsService;
use App\Services\Sms\SmsQueueProcessor;

final class SmsController extends Controller
{
    public function index(Request $request): never
    {
        $result = Database::instance()->paginate('SELECT * FROM sms_queue ORDER BY id DESC');
        $templates = SmsTemplate::query()->orderBy('key')->get();

        $this->view('sms.index', [
            'title' => 'SMS',
            'pageTitle' => 'SMS queue & templates',
            'queue' => $result['data'],
            'page' => $result['page'],
            'pages' => $result['pages'],
            'templates' => $templates,
            'gateway' => (string) config('sms.gateway', 'arkesel'),
            'senderId' => (string) config('sms.sender_id', 'PLPharma'),
            'apiConfigured' => (string) config('sms.api_key', '') !== '',
            'sandbox' => (bool) config('sms.sandbox', false),
        ]);
    }

    public function send(Request $request): never
    {
        $data = $this->validate($request->all(), [
            'to' => 'required',
            'message' => 'required',
        ]);

        (new QueuedSmsService())->send((string) $data['to'], (string) $data['message'], 'legal_notice');
        $this->backWithSuccess('Message queued.', '/sms');
    }

    public function process(Request $request): never
    {
        $counts = SmsQueueProcessor::make()->process();
        $this->backWithSuccess(
            sprintf('Queue processed: %d sent, %d retrying, %d failed.', $counts['sent'], $counts['skipped'], $counts['failed']),
            '/sms'
        );
    }
}
