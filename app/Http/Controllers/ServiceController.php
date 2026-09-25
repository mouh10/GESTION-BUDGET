<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServiceController extends Controller
{
    public function index()
    {
        return view('nomenclature.services', ['services' => Service::withCount('lignesCredit')->orderBy('code')->get()]);
    }

    public function create()
    {
        return view('nomenclature.service-form', ['service' => new Service(['actif' => true])]);
    }

    public function store(Request $request)
    {
        Service::create($this->valider($request) + ['actif' => true]);

        return redirect()->route('services.index')->with('succes', 'Service créé.');
    }

    public function edit(Service $service)
    {
        return view('nomenclature.service-form', compact('service'));
    }

    public function update(Request $request, Service $service)
    {
        $service->update($this->valider($request, $service) + ['actif' => $request->boolean('actif')]);

        return redirect()->route('services.index')->with('succes', 'Service mis à jour.');
    }

    public function destroy(Service $service)
    {
        if ($service->lignesCredit()->exists()) {
            return back()->with('erreur', 'Ce service gère des crédits : désactivez-le plutôt.');
        }
        $service->delete();

        return redirect()->route('services.index')->with('succes', 'Service supprimé.');
    }

    protected function valider(Request $request, ?Service $service = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('services', 'code')->ignore($service?->id)],
            'libelle' => ['required', 'string', 'max:255'],
            'responsable' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
