<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JournalController extends Controller
{
    public function index()
    {
        return view('journaux.index', ['journaux' => Journal::withCount('ecritures')->orderBy('code')->get()]);
    }

    public function create()
    {
        return view('journaux.form', ['journal' => new Journal(['type' => 'operations_diverses'])]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'alpha_num', 'max:5', 'unique:journaux,code'],
            'libelle' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(array_keys(Journal::TYPES))],
        ]);
        $data['code'] = strtoupper($data['code']);

        Journal::create($data);

        return redirect()->route('journaux.index')->with('succes', 'Journal créé.');
    }

    public function edit(Journal $journal)
    {
        return view('journaux.form', compact('journal'));
    }

    public function update(Request $request, Journal $journal)
    {
        $data = $request->validate([
            'libelle' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(array_keys(Journal::TYPES))],
        ]);

        $journal->update($data);

        return redirect()->route('journaux.index')->with('succes', 'Journal mis à jour.');
    }
}
