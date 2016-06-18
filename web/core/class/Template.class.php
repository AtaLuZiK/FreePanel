<?php
namespace system;
defined('BASE_PATH') OR exit('No direct script access allowed');
defined('TEMPLATE_PATH') or define('TEMPLATE_PATH', get_config('TEMPLATE_PATH', APP_PATH . 'view' . DIRECTORY_SEPARATOR));

/**
 * Template represents a view object in the MVC pattern.
 * Template file will be loaded in APP_PATH/view, it can be changed in configure named TEMPLATE_PATH
 * @example 'TEMPLATE_PATH' => APP_PATH . 'template'
 * or an array
 * @example 'TEMPLATE_PATH' => [
 *  'default' => APP_PATH . 'view',
 *  'root' => BASE_PATH . 'template'
 * ]
 * You can call $template->load('@root/index') to load the template.
 * If TEMPLATE_PATH not a array, will ignore the prefix.
 * If TEMPLATE_PATH is an array, and not found the file in specify prefix, will find in default node,
 * or the first path if unset __DEFAULT__ node.
 * DO NOT use "system" as node name, it is reserved.
 * @author AtaLuZiK
 *
 */
class Template
{
    private $variables;
    private $blocks;
    
    
    public function __construct()
    {
        $this->clear();
    }
    
    
    public function clear()
    {
        $this->variables = array();
        $this->blocks = array();
    }
    
    
    /**
     * load and show the specify template.
     * @param string $templateName
     * @example
     * show('index.html');
     * show('core@exception.html');
     */
    public function show($templateName)
    {
        $content = $this->load($templateName);
        $content = $this->render($content);
        header('Content-Type: text/html; charset=utf-8');
        echo $content;
    }
    
    
    public function assign($name, $value)
    {
        $this->variables[$name] = $value;
    }
    
    
    /**
     * load the template content
     * @param string $name
     * @return the template content
     */
    protected function load($name)
    {
        $templateFile = $this->findTemplateFile($name);
        if (!empty($templateFile) && file_exists($templateFile)) {
            $content = file_get_contents($templateFile);
            $content = $this->preprocessing($content);
            // parse layout
            if (preg_match('/<@layout\s+(?<params>.*?)\/>/', $content, $matches)) {
                $content = str_replace($matches[0], '', $content);
                // only parse block
                $this->parseBlocks($content, false);
                if (preg_match_all('/(\w+)\s*=\s*"([^"]+)"/', $matches['params'], $params)) {
                    $params = array_combine($params[1], $params[2]);
                    $layoutContent = $this->load($params['name']);
                    $layoutContent = $this->parseBlocks($layoutContent);
                    return $layoutContent;
                }
            }
            return $content;
        } else {
            throw new Exception("Not found template $name");
        }
    }
    
    
    /**
     * Finds the template file based on the given view name.
     * @param string $name The template name or the path alias of the template file.
     * If unset this parameter, default name is "CONTROLLER_NAME . '_' . ACTION_NAME"
     * @return string The template file path or null if the file not exists.
     */
    protected function findTemplateFile($name = '')
    {
        $prefix = '';
        if (empty($name)) {
            $name = CONTROLLER_NAME . '_' . ACTION_NAME;
        } else {
            if (strncmp($name, '@', 1) == 0) {
                list($prefix, $name) = explode('/', ltrim($name, '@'), 2);
            }
        }
        
        if ($prefix === 'system') {
            $filename = CORE_PATH . 'template' . DIRECTORY_SEPARATOR . $name . '.html';
            return file_exists($filename) ? $filename : null;
        }
        
        $name = $name . '.html';
        $searchPath = get_config('TEMPLATE_PATH', APP_PATH . 'view' . DIRECTORY_SEPARATOR);
        if (is_string($searchPath)) {
            $filename = path_combine($searchPath, $name);
        } else {
            if (array_key_exists($prefix, $searchPath)) {
                $path = $searchPath[$prefix];
            } else if (array_key_exists('default', $searchPath)) {
                $path = $searchPath['default'];
            } else {
                $path = $searchPath[0];
            }
            $filename = path_combine($path, $name);
        }
        
        if (!empty($filename) && file_exists($filename)) {
            return $filename;
        }
        
        return null;
    }
    
    
    protected function preprocessing($content)
    {
        // parse pre-processing tags first
        return preg_replace_callback('/<@(?<tagName>\w+)\s+(?<params>.*?)\/>/', function($matches) {
            preg_match_all('/(\w+)\s*=\s*"([^"]+)"/', $matches['params'], $params);
            $params = array_combine($params[1], $params[2]);
            $tagName = $matches['tagName'];
            switch ($tagName) {
                case 'include':
                    return $this->handleIncludeTag($tagName, $params);
            }
            return $matches[0];
        }, $content);
    }
    
    
    /**
     * render the content, this function will convert all template tags to php code
     * @param string $content
     * @return 
     */
    protected function render($content)
    {
        // parse contstant
        $content = preg_replace_callback('/__(.*?)__/', function($matches) {
            if (defined($matches[1]))
                return constant($matches[1]);
            return $matches[0];
        }, $content);
        
        // parse variables
        $content = preg_replace_callback('/{##(.*?)##}/', function($matches) {
            $variable = $matches[1];
            if ($variable[0] === '@') {
                $variable = 'get_config(\'' . ltrim($variable, '@') . '\')';
            } else {
                $variable = DEBUG_MODE ? $variable = '$' . $variable 
                                        : 'isset($' . $variable . ') ? $' . $variable . ' : \'\'';
            }
            return "<?php echo $variable; ?>";
        }, $content);
        ob_start();
        extract($this->variables);
        eval("?>$content");
        return ob_get_clean();
    }
    
    
    private function parseBlocks($content, $replace = true)
    {
        $pattern = '/{%\s*block\s+(?<name>\w+)\s*%}(?<content>([\s\S]*?)){%\s*endblock\s*%}/';
        return preg_replace_callback($pattern, function($matches) use($content, $replace) {
            $name = $matches['name'];
                    $blockContent = preg_replace('/(^[\r|\n|\s]*|[\r|\n\s]*$)/', '', $matches['content']);
            if ($replace === true) {
                return isset($this->blocks[$name]) ? $this->blocks[$name] : $blockContent;
            } else {
                if (!isset($this->blocks[$name])) {
                    $this->blocks[$name] = $blockContent;
                }
            }
        }, $content);
    }
    
    
    private function handleIncludeTag($tagName, $params)
    {
        if (isset($params['file'])) {
            include APP_PATH . $params['file'];
        }
    }
}
