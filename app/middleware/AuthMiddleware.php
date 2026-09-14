<?php

/**
 * @desc 面板访问安全与认证中间件
 * @author Tinywan(ShaoBo Wan)
 * @date 2026/09/14
 */

declare(strict_types=1);

namespace plugin\horizon\app\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class AuthMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response
    {
        $authConfig = config('plugin.horizon.app.auth', []);

        // 未开启认证则直接放行
        if (!($authConfig['enabled'] ?? false)) {
            return $handler($request);
        }

        $expectedUser = $authConfig['username'] ?? 'admin';
        $expectedPass = $authConfig['password'] ?? 'admin123';

        // HTTP Basic Auth 校验
        $authHeader = $request->header('authorization', '');
        if (empty($authHeader) || !str_starts_with($authHeader, 'Basic ')) {
            return $this->unauthorizedResponse();
        }

        $credentials = base64_decode(substr($authHeader, 6), true);
        if ($credentials === false) {
            return $this->unauthorizedResponse();
        }
        [$user, $pass] = explode(':', $credentials, 2) + ['', ''];

        if ($user !== $expectedUser || $pass !== $expectedPass) {
            return $this->unauthorizedResponse();
        }

        return $handler($request);
    }

    protected function unauthorizedResponse(): Response
    {
        return response('Unauthorized', 401, [
            'WWW-Authenticate' => 'Basic realm="Webman Horizon Dashboard"',
        ]);
    }
}
