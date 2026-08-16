<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PaceBoard API Documentation</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 900px; margin: 0 auto; padding: 2rem; color: #0f172a; line-height: 1.6; }
        h1 { font-size: 1.75rem; }
        h2 { font-size: 1.1rem; margin-top: 2rem; border-bottom: 1px solid #e2e8f0; padding-bottom: .5rem; }
        .auth-note { background: #eff6ff; border: 1px solid #bfdbfe; padding: 1rem; border-radius: 8px; margin: 1.5rem 0; }
        table { width: 100%; border-collapse: collapse; margin: 1rem 0; font-size: .9rem; }
        th, td { padding: .6rem .75rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { color: #64748b; font-weight: 600; }
        .method { font-weight: 700; font-size: .8rem; padding: .15rem .4rem; border-radius: 4px; }
        .POST { background: #dcfce7; color: #166534; }
        .GET { background: #dbeafe; color: #1e40af; }
        .PUT { background: #fef9c3; color: #854d0e; }
        .DELETE { background: #fee2e2; color: #991b1b; }
        .PATCH { background: #f3e8ff; color: #6b21a8; }
        code { background: #f1f5f9; padding: .1rem .35rem; border-radius: 4px; font-size: .85rem; }
        a { color: #2563eb; }
    </style>
</head>
<body>
    <h1>PaceBoard API</h1>
    <p>REST API for the PaceBoard driving tracker. Base URL: <code>{{ url('/api') }}</code></p>

    <div class="auth-note">
        <strong>Authentication:</strong> Most endpoints require a Bearer token from <code>POST /api/login</code>.
        Pass it as <code>Authorization: Bearer {token}</code>.
        <br><a href="{{ url('/api/docs/openapi.json') }}">Download OpenAPI 3.0 spec</a>
    </div>

    @foreach($endpoints as $group => $items)
    <h2>{{ $group }}</h2>
    <table>
        <tr><th>Method</th><th>Endpoint</th><th>Description</th><th>Auth</th></tr>
        @foreach($items as [$method, $path, $desc, $auth])
        <tr>
            <td><span class="method {{ $method }}">{{ $method }}</span></td>
            <td><code>{{ $path }}</code></td>
            <td>{{ $desc }}</td>
            <td>{{ $auth ? 'Yes' : 'No' }}</td>
        </tr>
        @endforeach
    </table>
    @endforeach
</body>
</html>
