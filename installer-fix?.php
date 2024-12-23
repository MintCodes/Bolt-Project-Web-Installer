<?php
// PROTOTYPE VERSION - Quick fix for simple files, will be updated later to handle complex folder structures
// Istg this shit doesnt work, i need to config my php correclty. But it still installs all dependencies for your project, this one just doesnt scan for "project" folder so it's pretty universal.
if (pathinfo($_SERVER['REQUEST_URI'], PATHINFO_EXTENSION) !== 'php') exit;

// Set script permissions to allow full access
chmod(__FILE__, 0777);

function scanForDependencies($directory = '.') {
    $dependencies = [];
    
    // Recursively find dependency files
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    $dependencyFiles = [
        'package.json' => 'npm',
        'composer.json' => 'composer', 
        'vite.config.js' => 'vite',
        'vite.config.ts' => 'vite',
        'tsconfig.json' => 'typescript',
        'cargo.toml' => 'rust',
        'go.mod' => 'go',
        'requirements.txt' => 'python',
        'Gemfile' => 'ruby'
    ];

    foreach ($iterator as $file) {
        if (in_array($file->getFilename(), array_keys($dependencyFiles))) {
            $path = $file->getPathname();
            $content = file_get_contents($path);
            $projectPath = dirname($path);
            $type = $dependencyFiles[$file->getFilename()];
            
            if ($type === 'npm' || $type === 'composer') {
                $json = json_decode($content, true);
                if ($json === null) {
                    echo "Error parsing {$path}\n";
                    continue;
                }
                
                // Check for various frontend dependencies
                $hasTailwind = isset($json['dependencies']['tailwindcss']) || isset($json['devDependencies']['tailwindcss']);
                $hasReact = isset($json['dependencies']['react']) || isset($json['devDependencies']['react']);
                $hasVite = isset($json['dependencies']['vite']) || isset($json['devDependencies']['vite']);
                $hasTypeScript = isset($json['dependencies']['typescript']) || isset($json['devDependencies']['typescript']);

                $dependencies[] = [
                    'path' => $projectPath,
                    'type' => $type,
                    'dependencies' => isset($json['dependencies']) ? $json['dependencies'] : [],
                    'devDependencies' => isset($json['devDependencies']) ? $json['devDependencies'] : [],
                    'features' => [
                        'tailwind' => $hasTailwind,
                        'react' => $hasReact,
                        'vite' => $hasVite,
                        'typescript' => $hasTypeScript
                    ]
                ];
            } else {
                $dependencies[] = [
                    'path' => $projectPath,
                    'type' => $type,
                    'content' => $content
                ];
            }
        }

        // Scan for TSX/JSX files
        if (preg_match('/\.(tsx|jsx|ts|js)$/', $file->getFilename())) {
            $projectPath = dirname($file->getPathname());
            if (!array_filter($dependencies, fn($dep) => $dep['path'] === $projectPath)) {
                $dependencies[] = [
                    'path' => $projectPath,
                    'type' => 'frontend',
                    'files' => [$file->getFilename()]
                ];
            }
        }
    }

    return $dependencies;
}

function installDependencies($dependencies) {
    // Set content type to HTML to prevent file downloads
    header('Content-Type: text/html');
    
    // Create status UI
    echo "<!DOCTYPE html>
    <html class='bg-gray-100'>
    <head>
        <script src='https://cdn.tailwindcss.com'></script>
        <title>Dependency Installation Status</title>
    </head>
    <body class='p-8'>
        <div class='max-w-4xl mx-auto bg-white rounded-lg shadow-lg p-6'>
            <div class='mb-4 p-4 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700'>
                <p class='font-bold'>⚠️ Prototype Version</p>
                <p>This is a quick-fix version that works with simple file structures. An updated version with full folder support is coming soon.</p>
            </div>
            <h1 class='text-3xl font-bold mb-6'>Installing Dependencies</h1>
            <div class='space-y-4'>";

    foreach ($dependencies as $project) {
        $path = $project['path'];
        echo "<div class='border rounded p-4'>
                <h2 class='text-xl font-semibold mb-2'>Project: {$path}</h2>";
        
        chdir($path);
        
        switch ($project['type']) {
            case 'npm':
                exec('which npm', $output, $returnVar);
                if ($returnVar === 0) {
                    echo "<p class='text-green-600'>Installing NPM dependencies...</p>";
                    exec('npm install');
                    if (isset($project['features'])) {
                        if ($project['features']['tailwind']) {
                            exec('npm install -D tailwindcss postcss autoprefixer');
                            exec('npx tailwindcss init -p');
                        }
                        if ($project['features']['typescript']) {
                            exec('npm install -D typescript @types/react @types/react-dom');
                        }
                    }
                } else {
                    echo "<p class='text-red-600'>NPM is not installed. Please install Node.js and NPM first.</p>";
                }
                break;

            case 'composer':
                exec('which composer', $output, $returnVar);
                if ($returnVar === 0) {
                    echo "<p class='text-green-600'>Installing Composer dependencies...</p>";
                    exec('composer install');
                } else {
                    echo "<p class='text-red-600'>Composer is not installed. Please install Composer first.</p>";
                }
                break;

            case 'vite':
                echo "<p class='text-green-600'>Setting up Vite configuration...</p>";
                exec('npm install -D vite @vitejs/plugin-react');
                break;

            default:
                echo "<p class='text-blue-600'>Found {$project['type']} project</p>";
        }
        
        echo "</div>";
    }

    echo "</div>
        <div class='mt-6 text-center text-green-600 font-bold'>
            All dependencies have been processed!
        </div>
    </div>
    </body>
    </html>";
}

// Scan current directory and install dependencies
$projectDependencies = scanForDependencies();

// Set content type to HTML to prevent file downloads
header('Content-Type: text/html');

if (empty($projectDependencies)) {
    echo "<!DOCTYPE html>
    <html class='bg-gray-100'>
    <head>
        <script src='https://cdn.tailwindcss.com'></script>
        <title>No Dependencies Found</title>
    </head>
    <body class='p-8'>
        <div class='max-w-4xl mx-auto bg-white rounded-lg shadow-lg p-6'>
            <div class='mb-4 p-4 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700'>
                <p class='font-bold'>⚠️ Prototype Version</p>
                <p>This is a quick-fix version that works with simple file structures. An updated version with full folder support is coming soon.</p>
            </div>
            <h1 class='text-3xl font-bold text-red-600'>No projects with dependencies found.</h1>
        </div>
    </body>
    </html>";
} else {
    installDependencies($projectDependencies);
}
?>
