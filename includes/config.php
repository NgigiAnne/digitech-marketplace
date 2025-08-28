// Error levels to handle
define('REPORTED_ERROR_LEVELS', E_ALL);

// Custom error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // Ignore errors that aren't in the reported levels
    if (!($errno & REPORTED_ERROR_LEVELS)) {
        return false;
    }

    $errorType = [
        E_ERROR             => 'Error',
        E_WARNING           => 'Warning',
        E_PARSE             => 'Parse Error',
        E_NOTICE            => 'Notice',
        E_CORE_ERROR        => 'Core Error',
        E_CORE_WARNING      => 'Core Warning',
        E_COMPILE_ERROR     => 'Compile Error',
        E_COMPILE_WARNING   => 'Compile Warning',
        E_USER_ERROR        => 'User Error',
        E_USER_WARNING      => 'User Warning',
        E_USER_NOTICE       => 'User Notice',
        E_STRICT            => 'Strict Notice',
        E_RECOVERABLE_ERROR => 'Recoverable Error',
        E_DEPRECATED        => 'Deprecated',
        E_USER_DEPRECATED   => 'User Deprecated'
    ][$errno] ?? 'Unknown Error';

    $errorMessage = sprintf(
        '[%s] %s in %s on line %d',
        $errorType,
        $errstr,
        $errfile,
        $errline
    );

    // Log the error with additional context
    error_log($errorMessage);

    // Handle API errors differently
    if (strpos($errfile, 'api/') !== false || 
        (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
        
        http_response_code(500);
        header('Content-Type: application/json');
        
        $response = [
            'success' => false,
            'message' => 'An unexpected error occurred',
            'error' => APP_ENV === 'development' ? $errorMessage : null
        ];
        
        echo json_encode($response);
        exit;
    }

    // For non-API errors in development, show detailed error
    if (APP_ENV === 'development') {
        echo "<div style='background:#f8d7da;border:1px solid #f5c6cb;padding:15px;margin:10px;border-radius:4px;'>";
        echo "<h3 style='color:#721c24;'>$errorType</h3>";
        echo "<p><strong>Message:</strong> $errstr</p>";
        echo "<p><strong>File:</strong> $errfile</p>";
        echo "<p><strong>Line:</strong> $errline</p>";
        echo "</div>";
    } else {
        // In production, show generic error page
        include __DIR__ . '/../views/errors/500.html';
    }

    // Don't execute PHP internal error handler
    return true;
});

// Custom exception handler
set_exception_handler(function($exception) {
    $errorMessage = sprintf(
        "[Exception] %s in %s on line %d\nStack Trace:\n%s",
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        $exception->getTraceAsString()
    );

    // Log the full exception
    error_log($errorMessage);

    // API error response
    if (strpos($exception->getFile(), 'api/') !== false || 
        (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
        
        $statusCode = method_exists($exception, 'getStatusCode') ? 
            $exception->getStatusCode() : 500;
        
        http_response_code($statusCode);
        header('Content-Type: application/json');
        
        $response = [
            'success' => false,
            'message' => $exception->getMessage(),
            'error' => APP_ENV === 'development' ? [
                'type' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => APP_ENV === 'development' ? $exception->getTrace() : null
            ] : null
        ];
        
        echo json_encode($response);
        exit;
    }

    // Web error response
    if (APP_ENV === 'development') {
        echo "<div style='background:#f8d7da;border:1px solid #f5c6cb;padding:15px;margin:10px;border-radius:4px;'>";
        echo "<h3 style='color:#721c24;'>" . get_class($exception) . "</h3>";
        echo "<p><strong>Message:</strong> " . $exception->getMessage() . "</p>";
        echo "<p><strong>File:</strong> " . $exception->getFile() . "</p>";
        echo "<p><strong>Line:</strong> " . $exception->getLine() . "</p>";
        echo "<pre>" . $exception->getTraceAsString() . "</pre>";
        echo "</div>";
    } else {
        // In production, show appropriate error page based on status code
        $statusCode = method_exists($exception, 'getStatusCode') ? 
            $exception->getStatusCode() : 500;
        
        $errorPage = __DIR__ . "/../views/errors/{$statusCode}.html";
        if (file_exists($errorPage)) {
            include $errorPage;
        } else {
            include __DIR__ . '/../views/errors/500.html';
        }
    }
});

// Shutdown function to catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && ($error['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR))) {
        // Handle fatal errors
        $errorHandler = set_error_handler(function() {});
        restore_error_handler();
        
        if ($errorHandler) {
            call_user_func(
                $errorHandler,
                $error['type'],
                $error['message'],
                $error['file'],
                $error['line']
            );
        }
    }
});