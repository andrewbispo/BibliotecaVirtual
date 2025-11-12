<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<footer class="footer">
    <div class="container py-5">
        <!-- seu conteúdo -->


        <!-- Grid principal usando Bootstrap .row e .col-* -->
        <div class="row">

            <!-- Coluna 1: Informações da Biblioteca Bethel -->
            <!-- Mobile: col-12 | Tablet: col-md-6 | Desktop: col-lg-5 (Ocupa 5 de 12 colunas) -->
            <div class="col-12 col-md-6 col-lg-5 mb-5 mb-lg-0">
                <div class="footer-logo-group">
                    <h3 class="footer-title"> <span class="icon-book-footer">📚</span> Biblioteca Bethel</h3>
                </div>
                <p class="footer-text-desc">
                    Curando as melhores coleções literárias desde 2012. Para os verdadeiros amantes da palavra escrita.
                </p>
                <!-- Links de Mídia Social -->
                <div class="footer-social-links">
                    <a href="#" class="footer-link-social" aria-label="Facebook">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z" />
                        </svg>
                    </a>
                    <a href="#" class="footer-link-social" aria-label="Instagram">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="20" height="20" x="2" y="2" rx="5" ry="5" />
                            <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z" />
                            <line x1="17.5" x2="17.5" y1="6.5" y2="6.5" />
                        </svg>
                    </a>
                    <a href="#" class="footer-link-social" aria-label="Twitter">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path
                                d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2 1.7-1.4 3-3.2 3.5-5.2C4 13.5 1 12.5 1 10.5c.5.5 1.2.7 2 .8C1.5 8.8 1.9 8.2 2 7.5c.5 1.5 1.4 2.8 2.6 3.8 2-1.5 4.5-2.5 7.5-2.5C14.7 9 17 11 18 13c-2.2 1.4-4.7 1.6-7.5.3" />
                        </svg>
                    </a>
                    <a href="#" class="footer-link-social" aria-label="E-mail">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="20" height="16" x="2" y="4" rx="2" />
                            <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Coluna 2: Navegação -->
            <!-- Mobile: col-6 | Tablet: col-md-3 | Desktop: col-lg-2 -->
            <div class="col-6 col-md-3 col-lg-2">
                <h4 class="footer-links-title">Navegação</h4>
                <ul class="footer-links-list">
                    <li><a href="#" class="footer-link-item">Sobre</a></li>
                    <li><a href="#" class="footer-link-item">Livros</a></li>
                    <li><a href="#" class="footer-link-item">Autores</a></li>
                    <li><a href="#" class="footer-link-item">Eventos</a></li>
                    <li><a href="#" class="footer-link-item">Contato</a></li>
                </ul>
            </div>

            <!-- Coluna 3: Coleções -->
            <!-- Mobile: col-6 | Tablet: col-md-3 | Desktop: col-lg-3 -->
            <div class="col-6 col-md-3 col-lg-3">
                <h4 class="footer-links-title">Coleções</h4>
                <ul class="footer-links-list">
                    <li><a href="#" class="footer-link-item">Poesia</a></li>
                    <li><a href="#" class="footer-link-item">Romance</a></li>
                    <li><a href="#" class="footer-link-item">Clássicos</a></li>
                    <li><a href="#" class="footer-link-item">Contemporâneo</a></li>
                    <li><a href="#" class="footer-link-item">Não-ficção</a></li>
                </ul>
            </div>

            <!-- Coluna 4: Legal -->
            <!-- Mobile: col-12 (nova linha) | Tablet: col-md-12 (nova linha) | Desktop: col-lg-2 -->
            <div class="col-12 col-md-12 col-lg-2">
                <h4 class="footer-links-title">Legal</h4>
                <ul class="footer-links-list">
                    <li><a href="#" class="footer-link-item">Política de Privacidade</a></li>
                    <li><a href="#" class="footer-link-item">Termos de Uso</a></li>
                    <li><a href="#" class="footer-link-item">Cookies</a></li>
                </ul>
            </div>

        </div>

        <!-- Direitos Autorais e Rodapé Final -->
        <div class="row mt-4 pt-3 border-top border-secondary">
            <div class="col-12 text-center">
                <div class="footer-copy">
                    <p class="mb-0">© 2025 Biblioteca Bethel. Todos os direitos reservados.</p>
                </div>
            </div>
        </div>
    </div>
</footer>