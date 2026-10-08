<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CareRule;
use App\Models\DentalService;
use App\Models\MessageTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CareRuleController extends Controller
{
    public function index(): Response
    {
        $this->authorize('leads.manage');

        return Inertia::render('Crm/CareRules/Index', [
            'rules' => CareRule::with(['messageTemplate', 'triggerService'])
                ->orderBy('trigger_event')->orderBy('delay_days')
                ->get()->map(fn ($r) => [
                    'id' => $r->id,
                    'name' => $r->name,
                    'trigger_event' => $r->trigger_event,
                    'trigger_event_label' => $this->eventLabel($r->trigger_event),
                    'trigger_service' => $r->triggerService?->name,
                    'delay_days' => $r->delay_days,
                    'template' => $r->messageTemplate->name,
                    'channel' => $r->messageTemplate->channel->label(),
                    'is_active' => $r->is_active,
                ]),
            'templates' => MessageTemplate::where('is_active', true)->orderBy('name')
                ->get()->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'channel' => $t->channel->label()]),
            'services' => DentalService::where('is_active', true)->orderBy('name')
                ->get()->map(fn ($s) => ['id' => $s->id, 'name' => $s->name]),
            'events' => [
                ['value' => 'appointment_completed', 'label' => 'Sau hoàn thành lịch hẹn'],
                ['value' => 'treatment_completed',   'label' => 'Sau hoàn thành điều trị'],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('leads.manage');

        $data = $this->validated($request);
        CareRule::create($data);

        return back()->with('success', 'Đã tạo quy tắc CSKH.');
    }

    public function update(Request $request, CareRule $careRule): RedirectResponse
    {
        $this->authorize('leads.manage');

        $careRule->update($this->validated($request));

        return back()->with('success', 'Đã cập nhật quy tắc.');
    }

    public function destroy(CareRule $careRule): RedirectResponse
    {
        $this->authorize('leads.manage');
        $careRule->delete();

        return back()->with('success', 'Đã xóa quy tắc.');
    }

    private function eventLabel(string $event): string
    {
        return match ($event) {
            'appointment_completed' => 'Sau hoàn thành lịch hẹn',
            'treatment_completed' => 'Sau hoàn thành điều trị',
            default => $event,
        };
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'trigger_event' => 'required|in:appointment_completed,treatment_completed',
            'trigger_service_id' => 'nullable|exists:dental_services,id',
            'delay_days' => 'required|integer|min:0|max:3650',
            'message_template_id' => 'required|exists:message_templates,id',
            'is_active' => 'boolean',
        ]);
    }
}
