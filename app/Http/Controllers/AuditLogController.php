<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Station;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = AuditLog::query()
            ->with('user')
            ->when($request->filled('module'), fn ($q, $v) => $q->where('module', $v))
            ->when($request->filled('action'), fn ($q, $v) => $q->where('action', $v))
            ->when($request->filled('search'), fn ($q, $s) => $q->where('description', 'like', "%{$s}%"))
            ->latest()
            ->paginate($this->perPage())
            ->withQueryString();

        return view('audit-logs.index', [
            'logs' => $logs,
            'modules' => AuditLog::query()->select('module')->distinct()->orderBy('module')->pluck('module'),
        ]);
    }
}