<?php
namespace verbb\tiptap;

use Craft;
use yii\base\Module as BaseModule;

class Tiptap extends BaseModule
{
    // Constants
    // =========================================================================

    public const ID = 'tiptap';


    // Public Methods
    // =========================================================================

    public function init(): void
    {
        parent::init();

        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return;
        }

        Craft::$app->getView()->registerTwigExtension(new twig\Extension());
    }
}
