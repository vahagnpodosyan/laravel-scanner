<?php
/**
 * Laravel File Explorer Script
 * Сканирует и отображает структуру выбранных компонентов Laravel-проекта
 */

// Функция для поиска корневой директории Laravel
function findLaravelRoot($startDir)
{
    $currentDir = $startDir;
    while (!is_file($currentDir . '/artisan')) {
        $parentDir = dirname($currentDir);
        if ($parentDir === $currentDir) {
            return false;
        }
        $currentDir = $parentDir;
    }
    return $currentDir;
}

// Определяем корневую директорию Laravel
$rootDir = findLaravelRoot(getcwd());
if (!$rootDir) {
    die("❌ Не удалось найти корневую директорию Laravel (ищется файл artisan).\n");
}

// Опции сканирования (по умолчанию все ВЫКЛЮЧЕНО)
$scanOptions = [
    'controllers' => false,
    'models' => false,
    'admin' => false,
    'config' => false,
    'routes' => false,
    'views' => false,
    'migrations' => false,
    'public' => false,
    'bootstrap' => false,
];

// Обработка GET-параметров для опций
if (PHP_SAPI !== 'cli') {
    foreach ($scanOptions as $option => $default) {
        if (isset($_GET[$option])) {
            $scanOptions[$option] = ($_GET[$option] === '1' || $_GET[$option] === 'true');
        }
    }
}

// CLI обработка аргументов
if (PHP_SAPI === 'cli' && $argc > 1) {
    for ($i = 1; $i < $argc; $i++) {
        $arg = $argv[$i];
        if (strpos($arg, '--') === 0) {
            $option = substr($arg, 2);
            if (array_key_exists($option, $scanOptions)) {
                $scanOptions[$option] = true;
            }
        }
    }
}

// Если в CLI не переданы аргументы и в вебе нет выбранных опций - показываем форму
$hasSelectedOptions = !empty(array_filter($scanOptions));
if (!$hasSelectedOptions && PHP_SAPI !== 'cli') {
    renderOptionsForm($scanOptions, $rootDir);
    exit;
}

// Функция для вывода формы с опциями
function renderOptionsForm($scanOptions, $rootDir)
{
    echo "<!DOCTYPE html>
<html lang='ru'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Laravel File Explorer - Настройки сканирования</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 2px solid #e91e63; padding-bottom: 10px; }
        .options-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
        .option-item { display: flex; align-items: center; }
        .option-item input[type='checkbox'] { margin-right: 10px; transform: scale(1.2); }
        .option-item label { font-size: 16px; cursor: pointer; }
        .scan-btn { background: #e91e63; color: white; border: none; padding: 12px 30px; font-size: 16px; border-radius: 5px; cursor: pointer; margin-top: 20px; }
        .scan-btn:hover { background: #d81b60; }
        .current-dir { background: #e7f3ff; padding: 10px; border-radius: 5px; margin: 15px 0; }
        .warning { background: #fff3cd; padding: 10px; border-radius: 5px; margin: 15px 0; border: 1px solid #ffeaa7; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔍 Laravel File Explorer</h1>
        
        <div class='current-dir'>
            <strong>📦 Корневая директория:</strong> $rootDir
        </div>
        
        <div class='warning'>
            <strong>⚠️ Внимание:</strong> Будут сканироваться ТОЛЬКО выбранные компоненты. Если ничего не выбрано - будет показана эта форма.
        </div>
        
        <form method='GET'>
            <h3>Выберите компоненты для сканирования:</h3>
            <div class='options-grid'>
                <div class='option-item'>
                    <input type='checkbox' id='controllers' name='controllers' value='1' " . ($scanOptions['controllers'] ? 'checked' : '') . ">
                    <label for='controllers'>🎮 Контроллеры (app/Http/Controllers)</label>
                </div>
                <div class='option-item'>
                    <input type='checkbox' id='models' name='models' value='1' " . ($scanOptions['models'] ? 'checked' : '') . ">
                    <label for='models'>📊 Модели (app/Models)</label>
                </div>
                <div class='option-item'>
                    <input type='checkbox' id='admin' name='admin' value='1' " . ($scanOptions['admin'] ? 'checked' : '') . ">
                    <label for='admin'>⚙️ Админка (app/Admin, app/Http/Controllers/Admin)</label>
                </div>
                <div class='option-item'>
                    <input type='checkbox' id='config' name='config' value='1' " . ($scanOptions['config'] ? 'checked' : '') . ">
                    <label for='config'>🔧 Конфиги (config/)</label>
                </div>
                <div class='option-item'>
                    <input type='checkbox' id='routes' name='routes' value='1' " . ($scanOptions['routes'] ? 'checked' : '') . ">
                    <label for='routes'>🛣️ Роуты (routes/)</label>
                </div>
                <div class='option-item'>
                    <input type='checkbox' id='views' name='views' value='1' " . ($scanOptions['views'] ? 'checked' : '') . ">
                    <label for='views'>👁️ Представления (resources/views)</label>
                </div>
                <div class='option-item'>
                    <input type='checkbox' id='migrations' name='migrations' value='1' " . ($scanOptions['migrations'] ? 'checked' : '') . ">
                    <label for='migrations'>🗃️ Миграции (database/migrations)</label>
                </div>
                <div class='option-item'>
                    <input type='checkbox' id='public' name='public' value='1' " . ($scanOptions['public'] ? 'checked' : '') . ">
                    <label for='public'>📁 Public (public/)</label>
                </div>
                <div class='option-item'>
                    <input type='checkbox' id='bootstrap' name='bootstrap' value='1' " . ($scanOptions['bootstrap'] ? 'checked' : '') . ">
                    <label for='bootstrap'>🚀 Bootstrap (bootstrap/)</label>
                </div>
            </div>
            <button type='submit' class='scan-btn'>🔍 Начать сканирование</button>
        </form>
    </div>
</body>
</html>";
}

// Определяем пути для сканирования на основе ВЫБРАННЫХ опций
$scanPaths = [];

if ($scanOptions['controllers']) {
    $scanPaths[] = 'app/Http/Controllers';
}

if ($scanOptions['models']) {
    $scanPaths[] = 'app/Models';
}

if ($scanOptions['admin']) {
    // Ищем административные части
    $adminPaths = ['app/Admin', 'app/Http/Controllers/Admin', 'resources/views/admin'];
    foreach ($adminPaths as $adminPath) {
        if (is_dir($rootDir . '/' . $adminPath)) {
            $scanPaths[] = $adminPath;
        }
    }
}

if ($scanOptions['config']) {
    $scanPaths[] = 'config';
}

if ($scanOptions['routes']) {
    $scanPaths[] = 'routes';
}

if ($scanOptions['views']) {
    $scanPaths[] = 'resources/views';
}

if ($scanOptions['migrations']) {
    $scanPaths[] = 'database/migrations';
}

if ($scanOptions['public']) {
    $scanPaths[] = 'public';
}

if ($scanOptions['bootstrap']) {
    $scanPaths[] = 'bootstrap';
}

// Исключаемые файлы/директории (только технические, не относящиеся к коду проекта)
$excludePatterns = [
    '/\.git/',
    '/vendor/',
    '/node_modules/',
    '/storage\/framework/',
    '/storage\/logs/',
    '/storage\/cache/',
    '/\.env\.example/',
    '/composer\.lock/',
    '/package-lock\.json/',
    '/yarn\.lock/',
];

// Собираем все файлы
$allFiles = [];
$treeStructure = [];

// Выводим информацию только если есть выбранные опции
if ($hasSelectedOptions) {
    echo "🔍 Сканирование выбранных компонентов Laravel-проекта...\n\n";
    echo "📦 Корневая директория: $rootDir\n\n";

    // Выводим активные опции
    echo "✅ Выбранные компоненты для сканирования:\n";
    $selectedCount = 0;
    foreach ($scanOptions as $option => $enabled) {
        if ($enabled) {
            echo "  ✓ $option\n";
            $selectedCount++;
        }
    }
    echo "\n";

    if ($selectedCount === 0) {
        echo "❌ Не выбрано ни одного компонента для сканирования.\n";
        if (PHP_SAPI !== 'cli') {
            echo "<br><a href='?'>← Вернуться к выбору компонентов</a>";
        }
        exit;
    }

    // Сканируем только выбранные пути
    foreach ($scanPaths as $path) {
        $fullPath = $rootDir . '/' . $path;
        if (is_file($fullPath)) {
            if (!in_array($path, $allFiles)) {
                $allFiles[] = $path;
                addFileToTree($treeStructure, $path);
            }
        } elseif (is_dir($fullPath)) {
            scanDirectory($fullPath, $path, $allFiles, $treeStructure, $excludePatterns, $rootDir);
        }
    }

    // Функция рекурсивного сканирования директории
    function scanDirectory($absDir, $relDir, &$files, &$tree, $excludePatterns, $rootDir)
    {
        $items = scandir($absDir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;

            $absPath = $absDir . '/' . $item;
            $relPath = ($relDir === '.') ? $item : $relDir . '/' . $item;

            // Пропускаем исключённые
            $skip = false;
            foreach ($excludePatterns as $pattern) {
                if (preg_match($pattern, $absPath) || preg_match($pattern, $relPath)) {
                    $skip = true;
                    break;
                }
            }
            if ($skip) continue;

            if (is_file($absPath)) {
                if (!in_array($relPath, $files)) {
                    $files[] = $relPath;
                    addFileToTree($tree, $relPath);
                }
            } elseif (is_dir($absPath)) {
                scanDirectory($absPath, $relPath, $files, $tree, $excludePatterns, $rootDir);
            }
        }
    }

    // Добавление файла в структуру дерева
    function addFileToTree(&$tree, $filePath)
    {
        $parts = explode('/', $filePath);
        $current = &$tree;
        foreach ($parts as $part) {
            if (!isset($current[$part])) {
                $current[$part] = [];
            }
            $current = &$current[$part];
        }
    }

    // Вывод дерева
    function printTree($tree, $prefix = '')
    {
        $keys = array_keys($tree);
        $count = count($keys);
        for ($i = 0; $i < $count; $i++) {
            $key = $keys[$i];
            $isLast = ($i === $count - 1);
            $connector = $isLast ? '└── ' : '├── ';
            echo $prefix . $connector . $key . "\n";

            if (!empty($tree[$key])) {
                $newPrefix = $prefix . ($isLast ? '    ' : '│   ');
                printTree($tree[$key], $newPrefix);
            }
        }
    }

    // Выводим иерархию
    if (!empty($treeStructure)) {
        echo "🌳 Иерархия файлов выбранных компонентов:\n";
        printTree($treeStructure);
        echo "\n" . str_repeat("─", 80) . "\n\n";
    } else {
        echo "❌ В выбранных компонентах не найдено файлов.\n";
        echo str_repeat("─", 80) . "\n\n";
    }

    // Выводим список файлов
    if (!empty($allFiles)) {
        echo "📋 Список всех файлов в выбранных компонентах:\n";
        foreach ($allFiles as $index => $file) {
            echo sprintf("%03d) %s\n", $index + 1, $file);
        }
        echo "\n" . str_repeat("─", 80) . "\n\n";

        // Выводим содержимое файлов
        echo "📄 Содержимое файлов:\n\n";

        foreach ($allFiles as $file) {
            echo str_repeat("═", 80) . "\n";
            echo "📂 Файл: " . $file . "\n";
            echo str_repeat("═", 80) . "\n";

            $absPath = $rootDir . '/' . $file;
            if (!file_exists($absPath)) {
                echo "❌ Файл не существует или недоступен для чтения.\n\n";
                continue;
            }

            $content = file_get_contents($absPath);
            if ($content === false) {
                echo "❌ Ошибка чтения файла.\n\n";
                continue;
            }

            if (PHP_SAPI === 'cli') {
                echo $content . "\n\n";
            } else {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                    highlight_string($content);
                } else {
                    echo "<pre>" . htmlspecialchars($content) . "</pre>";
                }
                echo "<br><br>";
            }
        }

        echo "✅ Сканирование выбранных компонентов завершено.\n";
        
        // Добавляем ссылку для возврата к выбору компонентов
        if (PHP_SAPI !== 'cli') {
            echo "<br><br><a href='?'>← Вернуться к выбору компонентов</a>";
        }
    } else {
        echo "❌ Не найдено файлов в выбранных компонентах.\n";
        if (PHP_SAPI !== 'cli') {
            echo "<br><a href='?'>← Вернуться к выбору компонентов</a>";
        }
    }
} else {
    // Если в CLI не переданы аргументы
    if (PHP_SAPI === 'cli') {
        echo "❌ Не выбрано ни одного компонента для сканирования.\n\n";
        echo "Использование:\n";
        echo "  php script.php --controllers --models --views\n\n";
        echo "Доступные опции:\n";
        foreach (array_keys($scanOptions) as $option) {
            echo "  --$option\n";
        }
    }
}
?>
