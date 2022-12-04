<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* partials/_work-with-us.html.twig */
class __TwigTemplate_dd793bd69e6e594484e8029366798b4f502db89d089e537f7683e218b2e3dc4b extends \Twig\Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        // line 1
        echo "<section class=\"ds parallax calltoaction section_padding_100\" style=\"background-image: url(";
        echo twig_escape_filter($this->env, $this->env->getExtension('Grav\Common\Twig\Extension\GravExtension')->urlFunc("theme://images/parallax/call_to_action.jpg"), "html", null, true);
        echo ")\">
    <div class=\"container\">
        <div class=\"row topmargin_60 bottommargin_60\">
            <div class=\"col-sm-12 text-center\">
                <h2 class=\"extra-big topmargin_0 bottommargin_30\">
                    ¿Quieres trabajar
                    <span class=\"highlight\">con nosotras?</span>
                </h2>
                <div class=\"row\">
                    <div class=\"col-md-offset-2 col-md-8 text-center\">
                        <p class=\"fontsize_20\">
                            Lorem ipsum dolor sit amet consectetur adipisicing elit. Eius eligendi maiores assumenda, cumque soluta error. Amet cum deserunt earum repudiandae autem eos excepturi dolor ipsam modi? Libero est sint beatae?
                        </p>
                    </div>
                </div>
                <div class=\"widget widget_mailchimp topmargin_20\">
                    <form class=\"signup form-inline\" action=\"#\" method=\"get\">
                        <div class=\"form-group\">
                            <input name=\"email\" type=\"email\" class=\"mailchimp_email form-control\" placeholder=\"";
        // line 19
        echo twig_escape_filter($this->env, $this->env->getExtension('Grav\Common\Twig\Extension\GravExtension')->translate($this->env, "THEME.ADDRESS"), "html", null, true);
        echo " de email\"/>
                        </div>
                        <button type=\"submit\" class=\"theme_button input_button\">Enviar</button>
                        <div class=\"response\"></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
";
    }

    public function getTemplateName()
    {
        return "partials/_work-with-us.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  52 => 19,  30 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Source("<section class=\"ds parallax calltoaction section_padding_100\" style=\"background-image: url({{ url('theme://images/parallax/call_to_action.jpg') }})\">
    <div class=\"container\">
        <div class=\"row topmargin_60 bottommargin_60\">
            <div class=\"col-sm-12 text-center\">
                <h2 class=\"extra-big topmargin_0 bottommargin_30\">
                    ¿Quieres trabajar
                    <span class=\"highlight\">con nosotras?</span>
                </h2>
                <div class=\"row\">
                    <div class=\"col-md-offset-2 col-md-8 text-center\">
                        <p class=\"fontsize_20\">
                            Lorem ipsum dolor sit amet consectetur adipisicing elit. Eius eligendi maiores assumenda, cumque soluta error. Amet cum deserunt earum repudiandae autem eos excepturi dolor ipsam modi? Libero est sint beatae?
                        </p>
                    </div>
                </div>
                <div class=\"widget widget_mailchimp topmargin_20\">
                    <form class=\"signup form-inline\" action=\"#\" method=\"get\">
                        <div class=\"form-group\">
                            <input name=\"email\" type=\"email\" class=\"mailchimp_email form-control\" placeholder=\"{{ 'THEME.ADDRESS'|t }} de email\"/>
                        </div>
                        <button type=\"submit\" class=\"theme_button input_button\">Enviar</button>
                        <div class=\"response\"></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
", "partials/_work-with-us.html.twig", "C:\\wamp\\www\\web-projects\\moon.agency\\user\\themes\\moon\\templates\\partials\\_work-with-us.html.twig");
    }
}
