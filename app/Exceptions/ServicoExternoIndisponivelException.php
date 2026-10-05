<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Falha de conexão com uma API de terceiros (ViaCEP, ReceitaWS) — distinta de
 * "a API respondeu que o recurso não existe". Capturada globalmente em
 * bootstrap/app.php e traduzida para HTTP 503, nunca confundida com um 404.
 */
class ServicoExternoIndisponivelException extends RuntimeException {}
