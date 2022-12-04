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

/* forms/layouts/form/default-form.html.twig */
class __TwigTemplate_9131e912317e89cf4c90af1c8bc03ae16acbda590fc228f720efc9b5bcbfdef1 extends \Twig\Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = [
            'embed_form_core' => [$this, 'block_embed_form_core'],
            'embed_form_classes' => [$this, 'block_embed_form_classes'],
            'embed_form_custom_attributes' => [$this, 'block_embed_form_custom_attributes'],
            'embed_fields' => [$this, 'block_embed_fields'],
            'embed_buttons' => [$this, 'block_embed_buttons'],
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        // line 1
        echo "<div class=\"row\">
    <div class=\"col-md-4\">
        <br/>
        <p class=\"bottommargin_20\">
            <span class=\"grey fontsize_12 text-uppercase bold\">";
        // line 5
        echo twig_escape_filter($this->env, $this->env->getExtension('Grav\Common\Twig\Extension\GravExtension')->translate($this->env, "THEME.ADDRESS"), "html", null, true);
        echo ":</span>
            <br/>
            ";
        // line 7
        echo twig_escape_filter($this->env, $this->getAttribute(($context["theme"] ?? null), "address", []));
        echo "
        </p>
        <p class=\"bottommargin_20\">
            <span class=\"grey fontsize_12 text-uppercase bold\">";
        // line 10
        echo twig_escape_filter($this->env, $this->env->getExtension('Grav\Common\Twig\Extension\GravExtension')->translate($this->env, "THEME.PHONE"), "html", null, true);
        echo ":</span>
            <br/>
            ";
        // line 12
        echo twig_escape_filter($this->env, $this->getAttribute(($context["theme"] ?? null), "phone", []));
        echo "
        </p>
        <p class=\"bottommargin_0\">
            <span class=\"grey fontsize_12 text-uppercase bold\">";
        // line 15
        echo twig_escape_filter($this->env, $this->env->getExtension('Grav\Common\Twig\Extension\GravExtension')->translate($this->env, "THEME.EMAIL"), "html", null, true);
        echo ":</span>
            <br/>
            <em>";
        // line 17
        echo twig_escape_filter($this->env, $this->getAttribute(($context["theme"] ?? null), "email", []));
        echo "</em>
        </p>
    </div>
    <div class=\"col-md-8\">
        <form class=\"contact-form row\" ";
        // line 21
        $this->displayBlock('embed_form_core', $context, $blocks);
        echo " ";
        $this->displayBlock('embed_form_classes', $context, $blocks);
        echo " ";
        $this->displayBlock('embed_form_custom_attributes', $context, $blocks);
        echo "> ";
        $this->displayBlock('embed_fields', $context, $blocks);
        // line 22
        echo "            <div class=\"col-md-6 topmargin_10\"> ";
        $this->displayBlock('embed_buttons', $context, $blocks);
        // line 23
        echo "                </div>
            </form>
        </div>
    </div>
</div>
";
    }

    // line 21
    public function block_embed_form_core($context, array $blocks = [])
    {
        echo " ";
    }

    public function block_embed_form_classes($context, array $blocks = [])
    {
        echo " ";
    }

    public function block_embed_form_custom_attributes($context, array $blocks = [])
    {
        echo " ";
    }

    public function block_embed_fields($context, array $blocks = [])
    {
    }

    // line 22
    public function block_embed_buttons($context, array $blocks = [])
    {
    }

    public function getTemplateName()
    {
        return "forms/layouts/form/default-form.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  115 => 22,  95 => 21,  86 => 23,  83 => 22,  75 => 21,  68 => 17,  63 => 15,  57 => 12,  52 => 10,  46 => 7,  41 => 5,  35 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Source("<div class=\"row\">
    <div class=\"col-md-4\">
        <br/>
        <p class=\"bottommargin_20\">
            <span class=\"grey fontsize_12 text-uppercase bold\">{{ 'THEME.ADDRESS'|t }}:</span>
            <br/>
            {{ theme.address|e }}
        </p>
        <p class=\"bottommargin_20\">
            <span class=\"grey fontsize_12 text-uppercase bold\">{{ 'THEME.PHONE'|t }}:</span>
            <br/>
            {{ theme.phone|e }}
        </p>
        <p class=\"bottommargin_0\">
            <span class=\"grey fontsize_12 text-uppercase bold\">{{ 'THEME.EMAIL'|t }}:</span>
            <br/>
            <em>{{ theme.email|e }}</em>
        </p>
    </div>
    <div class=\"col-md-8\">
        <form class=\"contact-form row\" {% block embed_form_core %} {% endblock %} {% block embed_form_classes %} {% endblock %} {% block embed_form_custom_attributes %} {% endblock %}> {% block embed_fields %}{% endblock %}
            <div class=\"col-md-6 topmargin_10\"> {% block embed_buttons %}{% endblock %}
                </div>
            </form>
        </div>
    </div>
</div>
", "forms/layouts/form/default-form.html.twig", "C:\\wamp\\www\\web-projects\\moon.agency\\user\\themes\\moon\\templates\\forms\\layouts\\form\\default-form.html.twig");
    }
}
