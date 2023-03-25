const colors = require("tailwindcss/colors");

module.exports = {
    content: [
        "../../config/**/*.yaml",
        "../../pages/**/*.md",
        "./blueprints/**/*.yaml",
        "./js/**/*.js",
        "./templates/**/*.twig",
        "./empiric.yaml",
        "./empiric.php",
    ],
    darkMode: "class", //false or 'media' or 'class'
    theme: {
        extend: {
            screens: {
                sm: "640px",
                md: "768px",
                lg: "1024px",
                xl: "1280px",
                "2xl": "1536px",
            },
        },
        fontFamily: {
            mont: ["Mont", "Sans-serif"],
        },
        colors: {
            primary: {
                lighter: colors.yellow["300"],
                DEFAULT: colors.yellow["400"],
                darker: colors.yellow["500"],
            },
            black: colors.black,
            gray: colors.zinc,
            white: colors.white,
            red: colors.red,
            green: colors.green,
            blue: colors.blue,
            orange: colors.orange,
            purple: colors.purple,
            yellow: colors.yellow,
            indigo: colors.indigo,
            transparent: "transparent",
            inherit: "inherit",
        },
        typography: (theme) => ({
            DEFAULT: {
                css: {
                    color: "inherit",
                    lineHeight: "inherit",
                    maxWidth: "inherit",
                    a: {
                        transition: "all 500ms",
                        color: theme("colors.primary.DEFAULT"),
                        "&:hover": {
                            color: theme("colors.primary.darker"),
                        },
                        textDecoration: "none",
                    },
                    strong: {
                        color: "inherit",
                    },
                },
            },
        }),
    },
    variants: {
        extend: {},
    },
    plugins: [
        require("@tailwindcss/forms"),
        require("@tailwindcss/typography"),
        require("tailwindcss-debug-screens"),
    ],
    important: false,
};
