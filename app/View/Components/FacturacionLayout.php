<?php

namespace App\View\Components;

use App\Models\Project;
use Illuminate\View\Component;
use Illuminate\View\View;

class FacturacionLayout extends Component
{
    public function __construct(
        public Project $project,
        public string $pageTitle = 'Dashboard'
    ) {}

    public function render(): View
    {
        return view('facturacion.layouts.app');
    }
}
