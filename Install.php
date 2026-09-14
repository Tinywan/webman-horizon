<?php

/**
 * @desc Webman Composer 生命周期安装与卸载器
 * @author Tinywan(ShaoBo Wan)
 * @date 2026/09/14
 */

declare(strict_types=1);

namespace plugin\horizon;

class Install
{
    public const WEBMAN_PLUGIN = true;

    /**
     * 定义需要自动同步到宿主 Webman 项目的目录/文件关系
     * 遵循 Webman 应用插件规范：宿主项目需存在 plugin/horizon 结构
     *
     * @var array<string, string>
     */
    protected static array $pathRelation = [
        'api' => 'plugin/horizon/api',
        'app' => 'plugin/horizon/app',
        'config' => 'plugin/horizon/config',
        'public' => 'plugin/horizon/public',
    ];

    /**
     * Install.
     *
     * @param bool $isFirstInstall
     * @return void
     */
    public static function install(bool $isFirstInstall = true): void
    {
        static::installByRelation();
    }

    /**
     * Update.
     *
     * @return void
     */
    public static function update(): void
    {
        static::installByRelation();
    }

    /**
     * Uninstall.
     *
     * @return void
     */
    public static function uninstall(): void
    {
        static::uninstallByRelation();
    }

    /**
     * 安装并同步文件到宿主项目 plugin/horizon
     *
     * @return void
     */
    public static function installByRelation(): void
    {
        if (!function_exists('base_path') || !function_exists('copy_dir')) {
            return;
        }

        foreach (static::$pathRelation as $source => $dest) {
            $destPath = base_path($dest);
            $parentDir = dirname($destPath);
            if (!is_dir($parentDir)) {
                mkdir($parentDir, 0777, true);
            }
            copy_dir(__DIR__ . "/{$source}", $destPath, true);
        }
    }

    /**
     * 卸载清理宿主项目 plugin/horizon 下的相关文件
     *
     * @return void
     */
    public static function uninstallByRelation(): void
    {
        if (!function_exists('base_path') || !function_exists('remove_dir')) {
            return;
        }

        // 优先尝试删除整个 plugin/horizon 目录
        $horizonRoot = base_path('plugin/horizon');
        if (is_dir($horizonRoot)) {
            remove_dir($horizonRoot);
            return;
        }

        foreach (static::$pathRelation as $dest) {
            $target = base_path($dest);
            if (is_dir($target) || is_file($target)) {
                remove_dir($target);
            }
        }
    }
}
