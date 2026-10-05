<!doctype html>
<html lang="pt-br">
    <head>
        <meta charset="utf-8" />
        <title>Fornecedores</title>
        <style>
            body {
                font-family: sans-serif;
                font-size: 11px;
                color: #212529;
            }

            h1 {
                font-size: 16px;
                margin-bottom: 4px;
            }

            .gerado-em {
                color: #6c757d;
                margin-bottom: 16px;
            }

            table {
                width: 100%;
                border-collapse: collapse;
            }

            th,
            td {
                border: 1px solid #dee2e6;
                padding: 6px 8px;
                text-align: left;
            }

            th {
                background-color: #f1f3f5;
            }

            .badge {
                padding: 2px 8px;
                border-radius: 4px;
                color: #fff;
                font-size: 10px;
            }

            .badge-ativo {
                background-color: #198754;
            }

            .badge-inativo {
                background-color: #dc3545;
            }
        </style>
    </head>
    <body>
        <h1>Fornecedores</h1>
        <div class="gerado-em">Gerado em {{ now()->format('d/m/Y H:i') }} — {{ $fornecedores->count() }} registro(s)</div>

        <table>
            <thead>
                <tr>
                    <th>Razão Social / Nome</th>
                    <th>Nome Fantasia / Apelido</th>
                    <th>CNPJ/CPF</th>
                    <th>Ativo</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($fornecedores as $fornecedor)
                    <tr>
                        <td>{{ $fornecedor->nome_exibicao }}</td>
                        <td>{{ $fornecedor->nome_fantasia_exibicao }}</td>
                        <td>{{ $fornecedor->cnpj_cpf_formatado }}</td>
                        <td>
                            <span class="badge {{ $fornecedor->ativo ? 'badge-ativo' : 'badge-inativo' }}">
                                {{ $fornecedor->ativo ? 'Ativo' : 'Inativo' }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </body>
</html>
