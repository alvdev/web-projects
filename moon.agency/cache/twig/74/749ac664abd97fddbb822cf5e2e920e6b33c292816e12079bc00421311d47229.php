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

/* partials/_where-are-we.html.twig */
class __TwigTemplate_6121ed7383fd5f65e21ecb4df4e1156d4b84b18afa055202631885d431e4849b extends \Twig\Template
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
        echo "<section class=\"ds map muted_section\">
    <div class=\"container\">
        <div class=\"row\">
            <div class=\"col-sm-12 text-center\">
                <div class=\"teaser text-center\">
                    <div class=\"teaser_icon black size_normal\">
                        <i class=\"rt-icon2-location2 highlight\"></i>
                    </div>
                </div>
                <a href=\"#\" class=\"theme_button\">";
        // line 10
        echo twig_escape_filter($this->env, $this->getAttribute(($context["theme"] ?? null), "address", []));
        echo "</a>
            </div>
        </div>
    </div>
</section>
";
    }

    public function getTemplateName()
    {
        return "partials/_where-are-we.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  41 => 10,  30 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Source("<section class=\"ds map muted_section\">
    <div class=\"container\">
        <div class=\"row\">
            <div class=\"col-sm-12 text-center\">
                <div class=\"teaser text-center\">
                    <div class=\"teaser_icon black size_normal\">
                        <i class=\"rt-icon2-location2 highlight\"></i>
                    </div>
                </div>
                <a href=\"#\" class=\"theme_button\">{{ theme.address|e }}</a>
            </div>
        </div>
    </div>
</section>
", "partials/_where-are-we.html.twig", "C:\\wamp\\www\\web-projects\\moon.agency\\user\\themes\\moon\\templates\\partials\\_where-are-we.html.twig");
    }
}
