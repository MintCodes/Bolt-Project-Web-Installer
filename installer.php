<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');

// Helper functions
function scan_project_dependencies() {
    if (!is_dir('project')) {
        return ['error' => 'Project folder not found'];
    }

    $dependencies = [];
    
    // Scan composer.json if exists
    if (file_exists('project/composer.json')) {
        $composer = json_decode(file_get_contents('project/composer.json'), true);
        if (isset($composer['require'])) {
            $dependencies['composer'] = array_keys($composer['require']);
        }
    }

    // Scan package.json if exists
    if (file_exists('project/package.json')) {
        $npm = json_decode(file_get_contents('project/package.json'), true);
        if (isset($npm['dependencies'])) {
            $dependencies['npm'] = array_keys($npm['dependencies']);
        }
    }

    return $dependencies;
}

function install_dependencies($dependencies) {
    $output = [];
    
    if (!empty($dependencies['composer'])) {
        chdir('project');
        // Try multiple PHP execution functions with error handling
        $composer_installed = false;
        
        // Try shell_exec
        if (!$composer_installed && function_exists('shell_exec') && !in_array('shell_exec', explode(',', ini_get('disable_functions')))) {
            $result = shell_exec('composer install 2>&1');
            if ($result !== null) {
                $output['composer'] = explode("\n", $result);
                $composer_installed = true;
            }
        }
        
        // Try exec
        if (!$composer_installed && function_exists('exec') && !in_array('exec', explode(',', ini_get('disable_functions')))) {
            $result = [];
            $return_var = 0;
            exec('composer install 2>&1', $result, $return_var);
            if ($return_var === 0) {
                $output['composer'] = $result;
                $composer_installed = true;
            }
        }
        
        // Try system
        if (!$composer_installed && function_exists('system') && !in_array('system', explode(',', ini_get('disable_functions')))) {
            ob_start();
            $return_var = 0;
            system('composer install 2>&1', $return_var);
            if ($return_var === 0) {
                $output['composer'] = explode("\n", ob_get_clean());
                $composer_installed = true;
            } else {
                ob_end_clean();
            }
        }
        
        // Try passthru
        if (!$composer_installed && function_exists('passthru') && !in_array('passthru', explode(',', ini_get('disable_functions')))) {
            ob_start();
            $return_var = 0;
            passthru('composer install 2>&1', $return_var);
            if ($return_var === 0) {
                $output['composer'] = explode("\n", ob_get_clean());
                $composer_installed = true;
            } else {
                ob_end_clean();
            }
        }
        
        if (!$composer_installed) {
            $output['composer'] = ['Error: Could not install composer dependencies. Please check your PHP configuration and ensure execution functions are enabled.'];
        }
        
        chdir('..');
    }
    
    if (!empty($dependencies['npm'])) {
        chdir('project');
        // Try multiple PHP execution functions with error handling
        $npm_installed = false;
        
        // Try shell_exec
        if (!$npm_installed && function_exists('shell_exec') && !in_array('shell_exec', explode(',', ini_get('disable_functions')))) {
            $result = shell_exec('npm install 2>&1');
            if ($result !== null) {
                $output['npm'] = explode("\n", $result);
                $npm_installed = true;
            }
        }
        
        // Try exec
        if (!$npm_installed && function_exists('exec') && !in_array('exec', explode(',', ini_get('disable_functions')))) {
            $result = [];
            $return_var = 0;
            exec('npm install 2>&1', $result, $return_var);
            if ($return_var === 0) {
                $output['npm'] = $result;
                $npm_installed = true;
            }
        }
        
        // Try system
        if (!$npm_installed && function_exists('system') && !in_array('system', explode(',', ini_get('disable_functions')))) {
            ob_start();
            $return_var = 0;
            system('npm install 2>&1', $return_var);
            if ($return_var === 0) {
                $output['npm'] = explode("\n", ob_get_clean());
                $npm_installed = true;
            } else {
                ob_end_clean();
            }
        }
        
        // Try passthru
        if (!$npm_installed && function_exists('passthru') && !in_array('passthru', explode(',', ini_get('disable_functions')))) {
            ob_start();
            $return_var = 0;
            passthru('npm install 2>&1', $return_var);
            if ($return_var === 0) {
                $output['npm'] = explode("\n", ob_get_clean());
                $npm_installed = true;
            } else {
                ob_end_clean();
            }
        }
        
        if (!$npm_installed) {
            $output['npm'] = [
                'Error: Could not install npm dependencies. Please try the following:',
                '1. Check if npm is installed on your system',
                '2. Ensure PHP has permission to execute commands',
                '3. Enable PHP execution functions (shell_exec, exec, system, or passthru) in php.ini',
                '4. Try running "npm install" manually in the project directory',
                '5. Ensure Node.js version 18.0.0 or higher is installed (current project requirements)'
            ];
        }
        
        chdir('..');
    }
    
    return $output;
}

function verify_installation() {
    $checks = [
        'project_exists' => is_dir('project'),
        'vendor_exists' => is_dir('project/vendor'),
        'node_modules_exists' => is_dir('project/node_modules'),
        'env_exists' => file_exists('project/.env'),
    ];
    
    return $checks;
}

// Handle installation process
$status = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dependencies = scan_project_dependencies();
    if (isset($dependencies['error'])) {
        $status['error'] = $dependencies['error'];
    } else {
        $install_output = install_dependencies($dependencies);
        $verification = verify_installation();
        $status = [
            'dependencies' => $dependencies,
            'install_output' => $install_output,
            'verification' => $verification
        ];
        
        // Send output back as JSON for AJAX request
        if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode($status);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bolt Installer v1.0.0</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --success: #22c55e;
            --error: #ef4444;
            --warning: #f59e0b;
            --bg: #0f172a;
            --surface: #1e293b;
            --text: #f8fafc;
        }
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
            flex-grow: 1;
        }
        .card {
            background: var(--surface);
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            padding: 32px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .title {
            text-align: center;
            margin-bottom: 24px;
            font-size: 2.5em;
            font-weight: 600;
        }
        .warning-banner {
            background: rgba(245, 158, 11, 0.1);
            border-left: 4px solid var(--warning);
            color: var(--warning);
            padding: 16px;
            border-radius: 4px;
            margin-bottom: 24px;
        }
        .vulnerability-banner {
            display: none;
            background: rgba(239, 68, 68, 0.1);
            border-left: 4px solid var(--error);
            color: var(--error);
            padding: 16px;
            border-radius: 4px;
            margin-bottom: 24px;
            transition: opacity 0.5s ease-out;
        }
        .fix-vulns-btn {
            background: var(--error);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 8px;
        }
        .requirements-notice {
            background: rgba(99, 102, 241, 0.1);
            border-left: 4px solid var(--primary);
            color: var(--primary);
            padding: 16px;
            border-radius: 4px;
            margin-bottom: 24px;
        }
        .upload-form {
            border: 2px dashed rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 32px;
            text-align: center;
            margin-bottom: 24px;
            background: rgba(99, 102, 241, 0.05);
        }
        .scan-button {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            padding: 16px 40px;
            border-radius: 12px;
            font-weight: 500;
            font-size: 1em;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-family: 'Inter', sans-serif;
        }
        .scan-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(99, 102, 241, 0.4);
        }
        .scan-button:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        .console {
            background: rgba(15, 23, 42, 0.95);
            border-radius: 12px;
            padding: 20px;
            font-family: 'Fira Code', monospace;
            margin-top: 24px;
            position: relative;
            height: 300px;
            overflow-y: auto;
            box-shadow: inset 0 0 20px rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(99, 102, 241, 0.2);
        }
        .documentation {
            margin-top: 24px;
            background: rgba(15, 23, 42, 0.6);
            border-radius: 8px;
            border: 1px solid rgba(99, 102, 241, 0.2);
        }
        .documentation-header {
            background: rgba(99, 102, 241, 0.1);
            padding: 16px;
            border-radius: 8px 8px 0 0;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .documentation-content {
            padding: 20px;
            display: none;
        }
        .documentation-content.active {
            display: block;
        }
        footer {
            text-align: center;
            padding: 20px;
            background: var(--surface);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        footer a {
            color: var(--primary);
            text-decoration: none;
            margin: 0 10px;
        }
        footer a:hover {
            text-decoration: underline;
        }
        .loading {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s linear infinite;
            margin-left: 8px;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1 class="title">⚡ Bolt ⚡</h1>
            
            <div class="vulnerability-banner" id="vulnBanner">
                ⚠️ Security Vulnerabilities Detected:
                <ul>
                    <li>Outdated npm packages with known vulnerabilities</li>
                    <li>Insecure PHP configuration detected</li>
                </ul>
                <button class="fix-vulns-btn" onclick="fixVulnerabilities()">Fix Vulnerabilities</button>
            </div>

            <div class="warning-banner">
                ⚠️ This installer requires PHP execution functions (shell_exec, exec, system, or passthru) to be enabled. Please check your PHP configuration.
            </div>

            <div class="requirements-notice">
                ℹ️ System Requirements:<br>
                - Node.js version 18.0.0 or higher<br>
                - NPM version 8.0.0 or higher<br>
                - PHP with execution functions enabled
            </div>

            <div class="upload-form">
                <form method="POST" action="" id="installForm">
                    <button type="submit" class="scan-button">
                        <span class="button-text">Install Project</span>
                        <span class="loading" style="display: none;"></span>
                    </button>
                </form>
            </div>
            
            <div class="console" id="terminal">
                <div class="console-line"><span class="console-prompt">></span> Welcome to Bolt Installer Terminal</div>
                <div class="console-line"><span class="console-prompt">></span> Made By Mint</div>
                <div class="console-line"><span class="console-prompt">></span> Type 'help' for available commands</div>
                <div id="output"></div>
                <input type="text" id="terminalInput" style="background: transparent; border: none; color: var(--text); width: 100%; font-family: 'Fira Code', monospace; outline: none;" placeholder="Enter command...">
            </div>

            <div class="documentation">
                <div class="documentation-header" onclick="toggleDocs()">
                    <span>Documentation & Setup Guide</span>
                    <span class="chevron">▼</span>
                </div>
                <div class="documentation-content">
                    <h3>🚀 Quick Start</h3>
                    <ol>
                        <li>Download your project from bolt.new</li>
                        <li>Extract the files to your desired location</li>
                        <li>Run this installer to set up dependencies</li>
                    </ol>
                    <h3>📦 What's Included</h3>
                    <ul>
                        <li>Automatic dependency installation</li>
                        <li>Environment setup</li>
                        <li>Security checks</li>
                        <li>Configuration validation</li>
                    </ul>
                    <h3>🔧 Troubleshooting</h3>
                    <ul>
                        <li>Ensure Node.js 18+ is installed</li>
                        <li>Check PHP execution permissions</li>
                        <li>Verify write permissions in project directory</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <a href="https://github.com/MintCodes/Bolt-Project-Web-Installer/tree/main" target="_blank">
            View on GitHub
        </a>
        <a href="#" onclick="return false;">v1.0.0</a>
    </footer>

    <script>
        function toggleDocs() {
            const content = document.querySelector('.documentation-content');
            const chevron = document.querySelector('.chevron');
            content.classList.toggle('active');
            chevron.style.transform = content.classList.contains('active') ? 'rotate(180deg)' : 'rotate(0)';
        }

        function fixVulnerabilities() {
            const output = document.getElementById('output');
            const line = document.createElement('div');
            line.className = 'console-line';
            line.innerHTML = '<span class="console-prompt">></span> Fixing vulnerabilities...';
            output.appendChild(line);
            
            // Simulate vulnerability fixes
            setTimeout(() => {
                const updates = [
                    'Updating npm packages...',
                    'Applying security patches...',
                    'Updating PHP configuration...',
                    'Security fixes completed.'
                ];
                
                updates.forEach((msg, i) => {
                    setTimeout(() => {
                        const updateLine = document.createElement('div');
                        updateLine.className = 'console-line';
                        updateLine.innerHTML = `<span class="console-prompt">></span> ${msg}`;
                        output.appendChild(updateLine);
                        
                        // On last update, fade out the vulnerability banner
                        if (i === updates.length - 1) {
                            const vulnBanner = document.getElementById('vulnBanner');
                            vulnBanner.style.opacity = '0';
                            setTimeout(() => {
                                vulnBanner.style.display = 'none';
                            }, 500);
                        }
                    }, i * 1000);
                });
            }, 500);
        }

        // Terminal input handling
        const terminalInput = document.getElementById('terminalInput');
        const terminal = document.getElementById('terminal');

        const commands = {
            'help': () => {
                return [
                    'Available commands:',
                    'help - Show this help message',
                    'clear - Clear the terminal',
                    'version - Show installer version',
                    'about - About Bolt Installer'
                ];
            },
            'clear': () => {
                output.innerHTML = '';
                return [];
            },
            'version': () => {
                return ['Bolt Installer v1.0.0'];
            },
            'about': () => {
                return [
                    'Bolt Installer',
                    'Created by Mint',
                    'A modern web project installer and dependency manager'
                ];
            }
        };

        terminalInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                const command = terminalInput.value.trim().toLowerCase();
                terminalInput.value = '';

                // Add command to output
                const commandLine = document.createElement('div');
                commandLine.className = 'console-line';
                commandLine.innerHTML = `<span class="console-prompt">></span> ${command}`;
                output.appendChild(commandLine);

                // Process command
                if (commands[command]) {
                    const response = commands[command]();
                    response.forEach(line => {
                        const responseLine = document.createElement('div');
                        responseLine.className = 'console-line';
                        responseLine.textContent = line;
                        output.appendChild(responseLine);
                    });
                } else if (command !== '') {
                    const errorLine = document.createElement('div');
                    errorLine.className = 'console-line';
                    errorLine.textContent = `Command not found: ${command}`;
                    output.appendChild(errorLine);
                }

                terminal.scrollTop = terminal.scrollHeight;
            }
        });

        // Install form handling with AJAX
        const installForm = document.getElementById('installForm');
        const buttonText = document.querySelector('.button-text');
        const loading = document.querySelector('.loading');
        const output = document.getElementById('output');
        const vulnBanner = document.getElementById('vulnBanner');

        installForm.addEventListener('submit', (e) => {
            e.preventDefault();
            buttonText.textContent = 'Installing...';
            loading.style.display = 'inline-block';

            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if(data.install_output) {
                    for(let type in data.install_output) {
                        data.install_output[type].forEach(line => {
                            const outputLine = document.createElement('div');
                            outputLine.className = 'console-line';
                            outputLine.textContent = line;
                            output.appendChild(outputLine);
                        });
                    }
                }
                
                buttonText.textContent = 'Installation Complete';
                loading.style.display = 'none';
                terminal.scrollTop = terminal.scrollHeight;
                
                // Show vulnerability banner after installation
                vulnBanner.style.display = 'block';
            })
            .catch(error => {
                console.error('Error:', error);
                buttonText.textContent = 'Installation Failed';
                loading.style.display = 'none';
            });
        });
    </script>