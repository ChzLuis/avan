<?php

namespace App\View\Components;

use App\Models\Project;
use Illuminate\View\Component;

class ComercialLayout extends Component
{
    public function __construct(
        public Project $project,
        public string $pageTitle = ''
    ) {}

    public function render()
    {
        return view('comercial.layouts.app');
    }
}
