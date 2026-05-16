<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;

class ProjectController extends Controller
{
    public function index(Request $request): Response
    {
        return Response::html($this->view('projects.index', [
            'title' => 'Projekty',
        ]));
    }

    public function show(Request $request): Response
    {
        return Response::html($this->view('projects.show', [
            'title' => 'Szczegóły projektu',
            'projectId' => $request->query('id'),
        ]));
    }
}
