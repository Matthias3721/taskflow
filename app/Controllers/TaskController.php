<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;

class TaskController extends Controller
{
    public function index(Request $request): Response
    {
        return Response::html($this->view('tasks.index', [
            'title' => 'Zadania',
        ]));
    }
}
