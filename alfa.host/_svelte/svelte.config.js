import preprocess from "svelte-preprocess";
/** @type {import('@sveltejs/kit').Config} */
const config = {
    kit: {
		// hydrate the <div id="svelte"> element in src/app.html
		target: '#svelte',
    //ssr: false,
    files: {
    }
	},

    preprocess: [preprocess({
        postcss: true
    })]
};

export default config;
