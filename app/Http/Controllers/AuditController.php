<?php

namespace App\Http\Controllers;

use App\Models\JournalAudit;
use App\Models\User;
use Illuminate\Http\Request;

/** Consultation du journal d'audit (administrateur). Le journal n'est ni modifiable ni supprimable. */
class AuditController extends Controller
{
    public function index(Request $request)
    {
        $entrees = JournalAudit::with('user')
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->user_id))
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->action))
            ->when($request->filled('type'), fn ($q) => $q->where('sujet_type', $request->type))
            ->when($request->filled('du'), fn ($q) => $q->where('created_at', '>=', $request->date('du')->startOfDay()))
            ->when($request->filled('au'), fn ($q) => $q->where('created_at', '<=', $request->date('au')->endOfDay()))
            ->when($request->filled('q'), function ($q) use ($request) {
                $t = '%'.$request->q.'%';
                $q->where(fn ($w) => $w->where('sujet_libelle', 'like', $t)->orWhere('description', 'like', $t)->orWhere('ip', 'like', $t));
            })
            ->orderByDesc('created_at')->orderByDesc('id')
            ->paginate(par_page(50))->withQueryString();

        return view('audit.index', [
            'entrees' => $entrees,
            'utilisateurs' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
