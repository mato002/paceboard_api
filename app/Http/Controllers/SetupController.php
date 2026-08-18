<?php

namespace App\Http\Controllers;

use App\Support\DatabaseBootstrapper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

class SetupController extends Controller
{
    public function migrate(Request $request): Response
    {
        $expected = (string) config('paceboard.setup_token');
        $provided = (string) $request->query('token');

        if ($expected === '' || $provided === '' || ! hash_equals($expected, $provided)) {
            abort(404);
        }

        try {
            $output = DatabaseBootstrapper::run();
        } catch (Throwable $e) {
            return response($e->getMessage(), 500)
                ->header('Content-Type', 'text/plain; charset=UTF-8');
        }

        return response($output)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
