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

/* partials/_subheader.html.twig */
class __TwigTemplate_4fac227ec4e52f03902513900e859ebd44d3b1bfe89edb0d3c9079c3cc1645b0 extends \Twig\Template
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
        echo "<header class=\"ds page_testimonials\">
    <div
        class=\"container\">
        <!-- Testimonials -->
        <div class=\"row topmargin_40 bottommargin_40\">
            <div class=\"col-sm-12 text-center\">
                <h1 class=\"big\">
                    ";
        // line 8
        echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute(($context["page"] ?? null), "header", []), "title", []), "html", null, true);
        echo "
                    <br/>
                    <span class=\"highlight muellerhoff\">Qué nos diferencia</span>
                </h1>

                <div class=\"testimonials-body\">
                    <blockquote class=\"blockquote-big\" data-slide=\"1\">
                        ";
        // line 15
        echo twig_escape_filter($this->env, $this->getAttribute(($context["theme"] ?? null), "testimonials", []), "html", null, true);
        echo "
                    </blockquote>
                </div>

                <div class=\"owl-carousel testimonials-carousel\" data-margin=\"0\" data-nav=\"true\" data-loop=\"true\" data-center=\"true\" data-items=\"3\" data-responsive-lg=\"3\" data-responsive-md=\"3\" data-responsive-sm=\"3\" data-responsive-xs=\"3\">
                    <div class=\"testimonial\">
                        <div class=\"author-photo\">
                            <img src=\"";
        // line 22
        echo twig_escape_filter($this->env, $this->env->getExtension('Grav\Common\Twig\Extension\GravExtension')->urlFunc("theme://images/team/02.jpg"), "html", null, true);
        echo "\" alt=\"\"/>
                        </div>
                        <div class=\"author-meta\">
                            <span class=\"bold highlight\">Nombre Apellido</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
";
    }

    public function getTemplateName()
    {
        return "partials/_subheader.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  59 => 22,  49 => 15,  39 => 8,  30 => 1,);
    }

    /** @deprecated since 1.27 (to be removed in 2.0). Use getSourceContext() instead */
    public function getSource()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.27 and will be removed in 2.0. Use getSourceContext() instead.', E_USER_DEPRECATED);

        return $this->getSourceContext()->getCode();
    }

    public function getSourceContext()
    {
        return new Source("<header class=\"ds page_testimonials\">
    <div
        class=\"container\">
        <!-- Testimonials -->
        <div class=\"row topmargin_40 bottommargin_40\">
            <div class=\"col-sm-12 text-center\">
                <h1 class=\"big\">
                    {{ page.header.title }}
                    <br/>
                    <span class=\"highlight muellerhoff\">Qué nos diferencia</span>
                </h1>

                <div class=\"testimonials-body\">
                    <blockquote class=\"blockquote-big\" data-slide=\"1\">
                        {{ theme.testimonials }}
                    </blockquote>
                </div>

                <div class=\"owl-carousel testimonials-carousel\" data-margin=\"0\" data-nav=\"true\" data-loop=\"true\" data-center=\"true\" data-items=\"3\" data-responsive-lg=\"3\" data-responsive-md=\"3\" data-responsive-sm=\"3\" data-responsive-xs=\"3\">
                    <div class=\"testimonial\">
                        <div class=\"author-photo\">
                            <img src=\"{{ url('theme://images/team/02.jpg') }}\" alt=\"\"/>
                        </div>
                        <div class=\"author-meta\">
                            <span class=\"bold highlight\">Nombre Apellido</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
", "partials/_subheader.html.twig", "C:\\wamp\\www\\web-projects\\moon.agency\\user\\themes\\moon\\templates\\partials\\_subheader.html.twig");
    }
}
