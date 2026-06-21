</main>

<footer class="border-t-2 border-pink mt-12 py-8 bg-bg relative" style="z-index: 2; box-shadow: 0 0 16px rgba(255,43,214,0.4);">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-center">
            <div>
                <span class="font-graffiti text-2xl tracking-widest text-pink uppercase neon-glow-pink">Diario</span><span class="font-graffiti text-2xl tracking-widest text-yellow uppercase">.Games</span>
            </div>
            <nav class="flex flex-wrap justify-center gap-4 font-body uppercase tracking-widest text-sm text-muted">
                <a href="/" class="hover:text-pink transition">Home</a>
                <a href="<?= url('games') ?>" class="hover:text-pink transition">Juegos</a>
                <a href="<?= url('search') ?>" class="hover:text-pink transition">Buscar</a>
                <a href="#" class="hover:text-pink transition">About</a>
                <a href="#" class="hover:text-pink transition">Contacto</a>
            </nav>
            <div class="flex justify-end gap-3 text-pink text-xl">
                <a href="#" aria-label="Twitter" class="hover:text-yellow transition"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23 3a10.9 10.9 0 01-3.14 1.53A4.48 4.48 0 0012 7.5v1A10.66 10.66 0 013 4.5s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z"/></svg></a>
                <a href="#" aria-label="Instagram" class="hover:text-yellow transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/></svg></a>
                <a href="#" aria-label="YouTube" class="hover:text-yellow transition"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23 7s-.2-1.5-.9-2.2c-.8-.9-1.7-.9-2.1-1C16.7 3.5 12 3.5 12 3.5s-4.7 0-8 .3c-.4 0-1.3 0-2.1 1C1.2 5.5 1 7 1 7S.8 8.7.8 10.5v1.9c0 1.8.2 3.5.2 3.5s.2 1.5.9 2.2c.8.9 1.9.9 2.4 1 1.7.2 7.7.3 7.7.3s4.7 0 8-.3c.4 0 1.3-.1 2.1-1 .7-.7.9-2.2.9-2.2s.2-1.7.2-3.5v-1.9c0-1.8-.2-3.5-.2-3.5zM9.5 14V8l6 3-6 3z"/></svg></a>
            </div>
        </div>
        <div class="mt-6 pt-4 border-t border-border flex flex-wrap justify-between items-center gap-2 text-xs text-muted font-body">
            <div>&copy; <?= date('Y') ?> Diario.Games. Todos los derechos reservados.</div>
            <div class="flex gap-4">
                <a href="#" class="hover:text-pink transition">Términos</a>
                <a href="#" class="hover:text-pink transition">Privacidad</a>
            </div>
        </div>
    </div>
</footer>

</body>
</html>
