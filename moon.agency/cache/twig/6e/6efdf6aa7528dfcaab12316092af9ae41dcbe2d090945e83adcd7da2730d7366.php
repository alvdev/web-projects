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

/* partials/_contact-us.html.twig */
class __TwigTemplate_2387d63bad462b1e303c2d3d46f6e1f0cc46e9774e7698a09232e0d148be1640 extends \Twig\Template
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
        echo "<section class=\"ds section_padding_70\">
    <div class=\"container\">
        <div class=\"row\">
            <div class=\"col-sm-12 text-center\">
                <h2 class=\"big margin_0\">Escríbenos</h2>
                <h2 class=\"muellerhoff topmargin_5 bottommargin_50 highlight\">
                    Formulario de contacto
                </h2>
            </div>
        </div>

        ";
        // line 12
        $this->loadTemplate("forms/form.html.twig", "partials/_contact-us.html.twig", 12)->display(twig_array_merge($context, ["form" => call_user_func_array($this->env->getFunction('forms')->getCallable(), ["contact-form"])]));
        // line 13
        echo "    </div>
</section>
";
    }

    public function getTemplateName()
    {
        return "partials/_contact-us.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  45 => 13,  43 => 12,  30 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Source("<section class=\"ds section_padding_70\">
    <div class=\"container\">
        <div class=\"row\">
            <div class=\"col-sm-12 text-center\">
                <h2 class=\"big margin_0\">Escríbenos</h2>
                <h2 class=\"muellerhoff topmargin_5 bottommargin_50 highlight\">
                    Formulario de contacto
                </h2>
            </div>
        </div>

        {% include \"forms/form.html.twig\" with { form: forms('contact-form')} %}
    </div>
</section>
", "partials/_contact-us.html.twig", "C:\\wamp\\www\\web-projects\\moon.agency\\user\\themes\\moon\\templates\\partials\\_contact-us.html.twig");
    }
}
