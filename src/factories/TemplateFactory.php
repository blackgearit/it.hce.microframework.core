<?php

namespace it\hce\microframework\core\factories;


use Jenssegers\Blade\Blade;
use it\hce\microframework\core\exceptions\ResourceWriteException;
use it\hce\microframework\core\MicroFramework;

class TemplateFactory
{
    const templatesExt = '.html';

    static $currentTemplate;
    static $jsLibs = false;
    static $ajaxFactory;
    static $templateName;
    static $componentsFactory;
    static $blade;

    /**
     * @param $templateName
     * @param $componentsArray
     * @param $headCssComponentName
     * @param bool $ajax
     * @return string
     */
    public static function loadTemplate($templateName, $componentsArray, $headCssComponentName, $ajax = false)
    {
        // Check if the request is AJAX type and load the factory
        self::$ajaxFactory = new AjaxFactory($ajax);

        // Load the template file
        self::$currentTemplate = file_get_contents(MicroFramework::getTemplatesPath() . $templateName . self::templatesExt);

        // If the current template is valid, load the components factory
        if (self::$currentTemplate) {

            // if we aren't in an AJAX context, write the resources
            if(! self::$ajaxFactory->isAjax()) {
                self::compileResources();
                self::writeJS();
            }

            return self::loadComponents($componentsArray, $headCssComponentName, self::$ajaxFactory->isAjax());
        }

        return false;
    }

//    /**
//     * Loads a Blade template
//     * @param string $templateName
//     * @return mixed
//     */
//    public static function loadBladeTemplate($templateName = 'homepage', $components) {
//        self::$blade = new Blade(MicroFramework::getTemplatesPath(), '');
//        return self::$blade->make($templateName, []);
//    }

    private static function compileResources() {
        self::compileJS();
        self::compileCSS();
    }

    private static function compileJS() {
        $targetJsPath = MicroFramework::getPublicPath() . 'js/main.js';

        // js routine
        if (file_exists($targetJsPath . ".lock") && file_exists($targetJsPath)) {
            // js is present and locked, do nothing
        } else {
            // Write minified JS to main.js
            $jsFactory = new JavascriptFactory();
            $jsFactory->collectJS();
            try {
                $jsFactory->write(MicroFramework::getPublicPath() . 'js/main.js');
            } catch (ResourceWriteException $e) {
                die($e->getMessage());
            }
        }
    }

    private static function compileCSS() {
        $targetCssPath = MicroFramework::getPublicPath() . 'css/main.css';

        // css routine
        if(file_exists($targetCssPath . ".lock") && file_exists($targetCssPath)) {
            // css is present and locked, do nothing
        } else {
            // Write minified CSS to main.css
            $sassFactory = new SassFactory();
            $sassFactory->collectSCSS();
            try {
                $sassFactory->write($targetCssPath);
            } catch (ResourceWriteException $e) {
                die($e->getMessage());
            }
        }
    }

    private static function loadComponents($componentsArray, $headCssComponentName, $isAjax) {
        // Load ComponentsFactory
        self::$componentsFactory = new ComponentsFactory(MicroFramework::getBasePath(), $componentsArray);

        // Load a possible headCss component and write it to the header
        $headCssComponent = self::$componentsFactory->loadHeadComponent($headCssComponentName);

        // Load the components array
        $components = self::$componentsFactory->loadComponents();

        // Write HTML
        self::writeTimestampOnTemplate();

        // save the results
        return self::writeComponents($components, $isAjax);
    }

    /**
     * Writes a timestamp just for development
     */
    private static function writeTimestampOnTemplate()
    {
        self::$currentTemplate = str_replace('{{{$time}}}', time(), self::$currentTemplate);
    }

    /**
     * Writes main.js path to header
     */
    private static function writeJS()
    {
        self::$currentTemplate = str_replace('{{{$jsFile}}}', '../js/main.js', self::$currentTemplate); // PHP COMPILED
    }

    /**
     * Writes components' HTML inside the template
     * @param array $components
     * @param bool $isAjax
     * @return mixed
     */
    private static function writeComponents($components, $isAjax)
    {
        $componentContent = '';
        foreach ($components as $component) {
            $componentContent .= $component->getHtml();
        }

        if($isAjax) {
            sleep(2); //TODO: ?
            $componentContent = json_encode($componentContent);
        }

        // save the result and return it
        return self::$currentTemplate = str_replace('{{{$components}}}', $componentContent, self::$currentTemplate);
    }
}