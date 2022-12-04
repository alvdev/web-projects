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

/* forms/fields/flex-objects/flex-objects.html.twig */
class __TwigTemplate_448a65481c31264d00d0228b3ebb645b9a3065e8deefe9cf3652935900305323 extends \Twig\Template
{
    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->blocks = [
            'global_attributes' => [$this, 'block_global_attributes'],
            'input' => [$this, 'block_input'],
        ];
    }

    protected function doGetParent(array $context)
    {
        // line 1
        return "forms/field.html.twig";
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        // line 8
        $context["macro"] = $this;
        // line 10
        $context["value"] = (((null === ($context["value"] ?? null))) ? ($this->getAttribute(($context["field"] ?? null), "default", [])) : (($context["value"] ?? null)));
        // line 1
        $this->parent = $this->loadTemplate("forms/field.html.twig", "forms/fields/flex-objects/flex-objects.html.twig", 1);
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 12
    public function block_global_attributes($context, array $blocks = [])
    {
        // line 13
        echo "    ";
        $this->displayParentBlock("global_attributes", $context, $blocks);
        echo "
    data-grav-field-name=\"";
        // line 14
        echo twig_escape_filter($this->env, $this->env->getExtension('Grav\Common\Twig\Extension\GravExtension')->fieldNameFilter((($context["scope"] ?? null) . $this->getAttribute(($context["field"] ?? null), "name", []))), "html", null, true);
        echo "\"
";
    }

    // line 17
    public function block_input($context, array $blocks = [])
    {
        // line 18
        echo "    ";
        $context["flex"] = $this->getAttribute(($context["grav"] ?? null), "flex_objects", [], "array");
        // line 19
        echo "    ";
        $context["all"] = $this->getAttribute(($context["flex"] ?? null), "blueprints", []);
        // line 20
        echo "    ";
        if (count(($context["all"] ?? null))) {
            // line 21
            echo "    ";
            $context["legacy"] = $this->getAttribute(($context["flex"] ?? null), "getLegacyBlueprintMap", [], "method");
            // line 22
            echo "    ";
            $context['_parent'] = $context;
            $context['_seq'] = twig_ensure_traversable(($context["all"] ?? null));
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
            foreach ($context['_seq'] as $context["label"] => $context["directory"]) {
                // line 23
                echo "        ";
                $context["url"] = $this->getAttribute($context["directory"], "blueprintFile", []);
                // line 24
                echo "        ";
                $context["found"] = twig_in_filter(($context["url"] ?? null), ($context["value"] ?? null));
                // line 25
                echo "        ";
                if (( !($context["found"] ?? null) && $this->getAttribute(($context["legacy"] ?? null), ($context["url"] ?? null), [], "array", true, true))) {
                    // line 26
                    echo "            ";
                    $context["found"] = twig_in_filter($this->getAttribute(($context["legacy"] ?? null), ($context["url"] ?? null), [], "array"), ($context["value"] ?? null));
                    // line 27
                    echo "        ";
                }
                // line 28
                echo "
        <div class=\"form-data block size-2-3\" data-grav-field=\"toggle\" data-grav-disabled=\"\" data-grav-default=\"null\" data-grav-field-name=\"";
                // line 29
                echo twig_escape_filter($this->env, $this->env->getExtension('Grav\Common\Twig\Extension\GravExtension')->fieldNameFilter((($context["scope"] ?? null) . $this->getAttribute(($context["field"] ?? null), "name", []))), "html", null, true);
                echo "[";
                echo twig_escape_filter($this->env, $this->getAttribute($context["loop"], "index0", []), "html", null, true);
                echo "]\">
        <div class=\"switch-toggle switch-grav switch-2\">
            ";
                // line 31
                $context["maxLen"] = 0;
                // line 32
                echo "            ";
                $context['_parent'] = $context;
                $context['_seq'] = twig_ensure_traversable([0 => "PLUGIN_ADMIN.ENABLED", 1 => "PLUGIN_ADMIN.DISABLED"]);
                foreach ($context['_seq'] as $context["_key"] => $context["text"]) {
                    // line 33
                    echo "                ";
                    $context["translation"] = (($this->getAttribute($this->getAttribute($this->getAttribute($this->getAttribute(($context["grav"] ?? null), "twig", [], "any", false, true), "twig", [], "any", false, true), "filters", [], "any", false, true), "tu", [], "array", true, true)) ? ($this->env->getExtension('Grav\Plugin\Admin\Twig\AdminTwigExtension')->tuFilter($context["text"])) : ($this->env->getExtension('Grav\Common\Twig\Extension\GravExtension')->translate($this->env, $context["text"])));
                    // line 34
                    echo "                ";
                    $context["maxLen"] = max(twig_length_filter($this->env, ($context["translation"] ?? null)), ($context["maxLen"] ?? null));
                    // line 35
                    echo "            ";
                }
                $_parent = $context['_parent'];
                unset($context['_seq'], $context['_iterated'], $context['_key'], $context['text'], $context['_parent'], $context['loop']);
                $context = array_intersect_key($context, $_parent) + $_parent;
                // line 36
                echo "
            ";
                // line 37
                $context["id"] = ((("toggle_" . $this->getAttribute(($context["field"] ?? null), "name", [])) . "_") . $context["label"]);
                // line 38
                echo "
            <input type=\"radio\"
                   value=\"";
                // line 40
                echo twig_escape_filter($this->env, ($context["url"] ?? null), "html", null, true);
                echo "\"
                   id=\"";
                // line 41
                echo twig_escape_filter($this->env, (($context["id"] ?? null) . "_yes"), "html", null, true);
                echo "\"
                   name=\"";
                // line 42
                echo twig_escape_filter($this->env, $this->env->getExtension('Grav\Common\Twig\Extension\GravExtension')->fieldNameFilter((($context["scope"] ?? null) . $this->getAttribute(($context["field"] ?? null), "name", []))), "html", null, true);
                echo "[";
                echo twig_escape_filter($this->env, $this->getAttribute($context["loop"], "index0", []), "html", null, true);
                echo "]\"
                   class=\"highlight\"
                   ";
                // line 44
                if (($context["found"] ?? null)) {
                    // line 45
                    echo "                   checked=\"checked\"
                   ";
                }
                // line 47
                echo "            />
            ";
                // line 48
                $context["text"] = "PLUGIN_ADMIN.ENABLED";
                // line 49
                echo "            ";
                $context["translation"] = twig_trim_filter((($this->getAttribute($this->getAttribute($this->getAttribute($this->getAttribute(($context["grav"] ?? null), "twig", [], "any", false, true), "twig", [], "any", false, true), "filters", [], "any", false, true), "tu", [], "array", true, true)) ? ($this->env->getExtension('Grav\Plugin\Admin\Twig\AdminTwigExtension')->tuFilter(($context["text"] ?? null))) : ($this->env->getExtension('Grav\Common\Twig\Extension\GravExtension')->translate($this->env, ($context["text"] ?? null)))));
                // line 50
                echo "            <label for=\"";
                echo twig_escape_filter($this->env, (($context["id"] ?? null) . "_yes"), "html", null, true);
                echo "\">";
                echo twig_trim_filter($context["macro"]->getspanToggle(($context["translation"] ?? null), ($context["maxLen"] ?? null)));
                echo "</label>
            <input type=\"radio\"
                   value=\"\"
                   name=\"";
                // line 53
                echo twig_escape_filter($this->env, $this->env->getExtension('Grav\Common\Twig\Extension\GravExtension')->fieldNameFilter((($context["scope"] ?? null) . $this->getAttribute(($context["field"] ?? null), "name", []))), "html", null, true);
                echo "[";
                echo twig_escape_filter($this->env, $this->getAttribute($context["loop"], "index0", []), "html", null, true);
                echo "]\"
                   id=\"";
                // line 54
                echo twig_escape_filter($this->env, (($context["id"] ?? null) . "_no"), "html", null, true);
                echo "\"
                   ";
                // line 55
                if ( !($context["found"] ?? null)) {
                    // line 56
                    echo "                   checked=\"checked\"
                   ";
                }
                // line 58
                echo "            />
            ";
                // line 59
                $context["text"] = "PLUGIN_ADMIN.DISABLED";
                // line 60
                echo "            ";
                $context["translation"] = twig_trim_filter((($this->getAttribute($this->getAttribute($this->getAttribute($this->getAttribute(($context["grav"] ?? null), "twig", [], "any", false, true), "twig", [], "any", false, true), "filters", [], "any", false, true), "tu", [], "array", true, true)) ? ($this->env->getExtension('Grav\Plugin\Admin\Twig\AdminTwigExtension')->tuFilter(($context["text"] ?? null))) : ($this->env->getExtension('Grav\Common\Twig\Extension\GravExtension')->translate($this->env, ($context["text"] ?? null)))));
                // line 61
                echo "            <label for=\"";
                echo twig_escape_filter($this->env, (($context["id"] ?? null) . "_no"), "html", null, true);
                echo "\">";
                echo twig_trim_filter($context["macro"]->getspanToggle(($context["translation"] ?? null), ($context["maxLen"] ?? null)));
                echo "</label>
        </div>
        <span title=\"";
                // line 63
                echo twig_escape_filter($this->env, $this->env->getExtension('Grav\Plugin\Admin\Twig\AdminTwigExtension')->tuFilter($this->getAttribute($context["directory"], "description", [])), "html", null, true);
                echo "\">";
                echo twig_escape_filter($this->env, $this->env->getExtension('Grav\Plugin\Admin\Twig\AdminTwigExtension')->tuFilter($this->getAttribute($context["directory"], "title", [])), "html", null, true);
                echo "</span>
        </div>
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
            unset($context['_seq'], $context['_iterated'], $context['label'], $context['directory'], $context['_parent'], $context['loop']);
            $context = array_intersect_key($context, $_parent) + $_parent;
            // line 66
            echo "    ";
        } else {
            // line 67
            echo "        <div>";
            echo twig_escape_filter($this->env, $this->env->getExtension('Grav\Plugin\Admin\Twig\AdminTwigExtension')->tuFilter("PLUGIN_FLEX_OBJECTS.ERROR.NO_FLEX_DIRECTORIES"), "html", null, true);
            echo "</div>
    ";
        }
    }

    // line 3
    public function getspanToggle($__input__ = null, $__length__ = null, ...$__varargs__)
    {
        $context = $this->env->mergeGlobals([
            "input" => $__input__,
            "length" => $__length__,
            "varargs" => $__varargs__,
        ]);

        $blocks = [];

        ob_start();
        try {
            // line 4
            echo "    ";
            $context["space"] = $this->env->getExtension('Grav\Common\Twig\Extension\GravExtension')->repeatFunc("&nbsp;&nbsp;", ((($context["length"] ?? null) - twig_length_filter($this->env, ($context["input"] ?? null))) / 2));
            // line 5
            echo "    ";
            echo ((($context["space"] ?? null) . ($context["input"] ?? null)) . ($context["space"] ?? null));
            echo "
";
        } catch (\Exception $e) {
            ob_end_clean();

            throw $e;
        } catch (\Throwable $e) {
            ob_end_clean();

            throw $e;
        }

        return ('' === $tmp = ob_get_clean()) ? '' : new Markup($tmp, $this->env->getCharset());
    }

    public function getTemplateName()
    {
        return "forms/fields/flex-objects/flex-objects.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  261 => 5,  258 => 4,  245 => 3,  237 => 67,  234 => 66,  215 => 63,  207 => 61,  204 => 60,  202 => 59,  199 => 58,  195 => 56,  193 => 55,  189 => 54,  183 => 53,  174 => 50,  171 => 49,  169 => 48,  166 => 47,  162 => 45,  160 => 44,  153 => 42,  149 => 41,  145 => 40,  141 => 38,  139 => 37,  136 => 36,  130 => 35,  127 => 34,  124 => 33,  119 => 32,  117 => 31,  110 => 29,  107 => 28,  104 => 27,  101 => 26,  98 => 25,  95 => 24,  92 => 23,  74 => 22,  71 => 21,  68 => 20,  65 => 19,  62 => 18,  59 => 17,  53 => 14,  48 => 13,  45 => 12,  40 => 1,  38 => 10,  36 => 8,  30 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Source("{% extends \"forms/field.html.twig\" %}

{% macro spanToggle(input, length) %}
    {% set space = repeat('&nbsp;&nbsp;', (length - input|length) / 2) %}
    {{ (space ~ input ~ space)|raw }}
{% endmacro %}

{% import _self as macro %}

{% set value = (value is null ? field.default : value) %}

{% block global_attributes %}
    {{ parent() }}
    data-grav-field-name=\"{{ (scope ~ field.name)|fieldName }}\"
{% endblock %}

{% block input %}
    {% set flex = grav['flex_objects'] %}
    {% set all = flex.blueprints %}
    {% if all|count %}
    {% set legacy = flex.getLegacyBlueprintMap() %}
    {% for label, directory in all %}
        {% set url = directory.blueprintFile %}
        {% set found = url in value %}
        {% if not found and legacy[url] is defined %}
            {% set found = legacy[url] in value %}
        {% endif %}

        <div class=\"form-data block size-2-3\" data-grav-field=\"toggle\" data-grav-disabled=\"\" data-grav-default=\"null\" data-grav-field-name=\"{{ (scope ~ field.name)|fieldName }}[{{ loop.index0 }}]\">
        <div class=\"switch-toggle switch-grav switch-2\">
            {% set maxLen = 0 %}
            {% for text in ['PLUGIN_ADMIN.ENABLED', 'PLUGIN_ADMIN.DISABLED'] %}
                {% set translation = grav.twig.twig.filters['tu'] is defined ? text|tu : text|t %}
                {% set maxLen = max(translation|length, maxLen) %}
            {% endfor %}

            {% set id = \"toggle_\" ~ field.name ~ '_' ~ label %}

            <input type=\"radio\"
                   value=\"{{ url }}\"
                   id=\"{{ id ~ '_yes' }}\"
                   name=\"{{ (scope ~ field.name)|fieldName }}[{{ loop.index0 }}]\"
                   class=\"highlight\"
                   {% if found %}
                   checked=\"checked\"
                   {% endif %}
            />
            {% set text = 'PLUGIN_ADMIN.ENABLED' %}
            {% set translation = (grav.twig.twig.filters['tu'] is defined ? text|tu : text|t)|trim %}
            <label for=\"{{ id ~ '_yes' }}\">{{ (macro.spanToggle(translation, maxLen)|trim)|raw }}</label>
            <input type=\"radio\"
                   value=\"\"
                   name=\"{{ (scope ~ field.name)|fieldName }}[{{ loop.index0 }}]\"
                   id=\"{{ id ~ '_no' }}\"
                   {% if not found %}
                   checked=\"checked\"
                   {% endif %}
            />
            {% set text = 'PLUGIN_ADMIN.DISABLED' %}
            {% set translation = (grav.twig.twig.filters['tu'] is defined ? text|tu : text|t)|trim %}
            <label for=\"{{ id ~ '_no' }}\">{{ (macro.spanToggle(translation, maxLen)|trim)|raw }}</label>
        </div>
        <span title=\"{{ directory.description|tu }}\">{{ directory.title|tu }}</span>
        </div>
    {% endfor %}
    {% else %}
        <div>{{ 'PLUGIN_FLEX_OBJECTS.ERROR.NO_FLEX_DIRECTORIES'|tu }}</div>
    {% endif %}
{% endblock %}", "forms/fields/flex-objects/flex-objects.html.twig", "C:\\wamp\\www\\web-projects\\moon.agency\\user\\plugins\\flex-objects\\admin\\templates\\forms\\fields\\flex-objects\\flex-objects.html.twig");
    }
}
