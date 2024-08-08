<script defer>
    window.onload = () => {
        var toc = "";
        var level = 0;
        document.querySelector("main").innerHTML =
            document.querySelector("main").innerHTML.replace(
                /<h([\d])>([^<]+)<\/h([\d])>/gi,
                function(str, openLevel, titleText, closeLevel) {
                    if (openLevel != closeLevel) {
                        return str;
                    }
                    if (openLevel > level) {
                        toc += (new Array(openLevel - level + 1)).join("<ul>");
                    } else if (openLevel < level) {
                        toc += (new Array(level - openLevel + 1)).join("</ul>");
                    }
                    level = parseInt(openLevel);
                    var anchor = titleText.replace(/ /g, "_");
                    toc += "<li class='mt-4'><a class='text-sky-600 hover:text-black' href=\"#" + anchor + "\">" +
                        titleText +
                        "</a></li>";
                    return "<h" + openLevel + "><a name=\"" + anchor + "\">" +
                        titleText + "</a></h" + closeLevel + ">";
                }
            );
        if (level) toc += (new Array(level + 1)).join("</ul>");
        document.getElementById("toc").innerHTML += toc;
    }
</script>
