<?php

namespace AlexaCRM\WordpressCRM\Shortcode\Twig;

use Twig_Error;
use Exception;

abstract class Template extends \Twig_Template {

    protected function displayWithErrorHandling(array $context, array $blocks = array())
    {
        try {
            $this->doDisplay($context, $blocks);
        } catch (Twig_Error $e) {
            if (!$e->getSourceContext()) {
                $e->setSourceContext($this->getSourceContext());
            }

            // this is mostly useful for Twig_Error_Loader exceptions
            // see Twig_Error_Loader
            if (false === $e->getTemplateLine()) {
                $e->setTemplateLine(-1);
                $e->guess();
            }

            throw $e;
        } catch (Exception $e) {
            ?>
            <div class="alert alert-danger"><?php _e( 'Unexpected error. Please try again.', 'integration-dynamics' ); ?>
            <?php if ( WP_DEBUG ) {
                printf('<br>An exception has been thrown during the rendering of a template ("%s").', $e->getMessage() );
            } ?>
            </div>
<?php
        }
    }

}
