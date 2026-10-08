<?php

namespace App\Http\Controllers\Crm;

use App\Enums\MessageChannel;
use App\Enums\MessageLogStatus;
use App\Http\Controllers\Controller;
use App\Models\MessageLog;
use App\Models\MessageTemplate;
use App\Models\Patient;
use App\Services\MessageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MessageController extends Controller
{
    public function __construct(private MessageService $service) {}

    public function templates(Request $request): Response
    {
        $this->authorize('leads.manage');

        $query = MessageTemplate::orderBy('channel')->orderBy('name');

        return Inertia::render('Crm/Messages/Templates', [
            'templates' => $query->get()->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'channel' => $t->channel->value,
                'channel_label' => $t->channel->label(),
                'content' => $t->content,
                'is_active' => $t->is_active,
            ]),
            'channels' => collect(MessageChannel::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
        ]);
    }

    public function storeTemplate(Request $request): RedirectResponse
    {
        $this->authorize('leads.manage');

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'channel' => 'required|in:sms,zalo',
            'content' => 'required|string',
            'is_active' => 'boolean',
        ]);

        MessageTemplate::create($data);

        return back()->with('success', 'Đã tạo mẫu tin nhắn.');
    }

    public function updateTemplate(Request $request, MessageTemplate $template): RedirectResponse
    {
        $this->authorize('leads.manage');

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'channel' => 'required|in:sms,zalo',
            'content' => 'required|string',
            'is_active' => 'boolean',
        ]);

        $template->update($data);

        return back()->with('success', 'Đã cập nhật mẫu.');
    }

    public function destroyTemplate(MessageTemplate $template): RedirectResponse
    {
        $this->authorize('leads.manage');

        if ($template->careRules()->exists()) {
            return back()->with('error', 'Mẫu đang được dùng bởi quy tắc CSKH, không thể xóa.');
        }

        $template->delete();

        return back()->with('success', 'Đã xóa mẫu.');
    }

    public function log(Request $request): Response
    {
        $this->authorize('leads.manage');

        $query = MessageLog::with(['patient', 'template'])
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->channel, fn ($q, $v) => $q->where('channel', $v))
            ->orderByDesc('id');

        return Inertia::render('Crm/Messages/Log', [
            'logs' => $query->paginate(30)->through(fn ($l) => [
                'id' => $l->id,
                'patient' => $l->patient->full_name,
                'phone' => $l->phone,
                'channel' => $l->channel,
                'template' => $l->template?->name,
                'content_sent' => $l->content_sent,
                'status' => $l->status->value,
                'status_label' => $l->status->label(),
                'status_color' => $l->status->color(),
                'sent_at' => $l->sent_at?->format('d/m/Y H:i'),
                'error_message' => $l->error_message,
            ]),
            'filters' => $request->only(['status', 'channel']),
            'statuses' => collect(MessageLogStatus::cases())->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()]),
            'channels' => collect(MessageChannel::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        $this->authorize('leads.manage');

        $data = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'template_id' => 'required|exists:message_templates,id',
        ]);

        $patient = Patient::findOrFail($data['patient_id']);
        $template = MessageTemplate::findOrFail($data['template_id']);

        $log = $this->service->send($patient, $template);

        $msg = $log->status->value === 'sent' ? 'Đã gửi tin nhắn.' : 'Gửi thất bại: '.$log->error_message;

        return back()->with($log->status->value === 'sent' ? 'success' : 'error', $msg);
    }
}
