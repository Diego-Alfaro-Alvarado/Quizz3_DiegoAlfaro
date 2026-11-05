<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CursoController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $curso = Curso::registrar($request->all());

        return response()->json($curso, 201);
    }
}