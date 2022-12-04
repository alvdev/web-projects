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

/* partials/legal-links.html.twig */
class __TwigTemplate_4d833701e3dec84877e2164006864b651faf32080e3937533ef6d90239bc2d6a extends \Twig\Template
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
        $context["options"] = ["items" => ["@page.children" => "/legal"]];
        // line 2
        $context["links"] = $this->getAttribute(($context["page"] ?? null), "collection", [0 => ($context["options"] ?? null)], "method");
        // line 3
        echo "
<ul class=\"list2 bottommargin_0\">
    ";
        // line 5
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable($this->getAttribute(($context["links"] ?? null), "visible", []));
        $context['loop'] = [
          'parent' => $context['_parent'],
          'index0' => 0,
          'index'  => 1,
          'first'  => true,
        ];
        if (is_array($context['_seq']) || (is_object($context['_seq']) && $context['_seq'] instanceof \Countable)) {
            $length = count($context['_seq']);
            $context['loop']['revindex0'] = $length - 1;
            $context['loop']['revindex'] = $length;
            $context['loop']['length'] = $length;
            $context['loop']['last'] = 1 === $length;
        }
        foreach ($context['_seq'] as $context["_key"] => $context["link"]) {
            // line 6
            echo "        ";
            $context["current_page"] = ((($this->getAttribute(($context["page"] ?? null), "active", []) || $this->getAttribute(($context["page"] ?? null), "activeChild", []))) ? ("active") : (""));
            // line 7
            echo "        <li class=\"menu-item-";
            echo twig_escape_filter($this->env, $this->getAttribute($context["loop"], "index", []), "html", null, true);
            echo " ";
            echo twig_escape_filter($this->env, ($context["current_page"] ?? null), "html", null, true);
            echo "\">
            <a href=\"";
            // line 8
            echo twig_escape_filter($this->env, $this->getAttribute($context["link"], "url", []), "html", null, true);
            echo "\">
                ";
            // line 9
            echo twig_escape_filter($this->env, $this->getAttribute($context["link"], "menu", []), "html", null, true);
            echo "
            </a>
        </li>
    ";
            ++$context['loop']['index0'];
            ++$context['loop']['index'];
            $context['loop']['first'] = false;
            if (isset($context['loop']['length'])) {
                --$context['loop']['revindex0'];
                --$context['loop']['revindex'];
                $context['loop']['last'] = 0 === $context['loop']['revindex0'];
            }
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['link'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 13
        echo "</ul>
";
    }

    public function getTemplateName()
    {
        return "partials/legal-links.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  87 => 13,  69 => 9,  65 => 8,  58 => 7,  55 => 6,  38 => 5,  34 => 3,  32 => 2,  30 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Source("{% set options = {items: {'@page.children': '/legal'}} %}
{% set links = page.collection(options) %}

<ul class=\"list2 bottommargin_0\">
    {% for link in links.visible %}
        {% set current_page = (page.active or page.activeChild) ? 'active' : '' %}
        <li class=\"menu-item-{{ loop.index }} {{ current_page }}\">
            <a href=\"{{ link.url }}\">
                {{ link.menu }}
            </a>
        </li>
    {% endfor %}
</ul>
", "partials/legal-links.html.twig", "C:\\wamp\\www\\web-projects\\moon.agency\\user\\themes\\moon\\templates\\partials\\legal-links.html.twig");
    }
}
