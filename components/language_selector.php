<?php
/**
 * Componente de Selector de Idioma
 * Permite cambiar el idioma de la interfaz
 */

function renderLanguageSelector($currentLang = null, $showFlags = true, $dropdown = true, $navbarStyle = false) {
    if ($currentLang === null) {
        $currentLang = getCurrentLanguage();
    }
    
    $languages = AVAILABLE_LANGUAGES;
    $flags = [
        'es' => '🇪🇸',
        'en' => '🇺🇸'
    ];
    
    ob_start();
    
    if ($dropdown) {
        $buttonClass = $navbarStyle ? 'btn btn-outline-light btn-sm dropdown-toggle' : 'btn btn-outline-secondary btn-sm dropdown-toggle';
        ?>
        <div class="dropdown">
            <button class="<?php echo $buttonClass; ?>" type="button" id="languageDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <?php if ($showFlags): ?>
                    <span class="me-1"><?php echo $flags[$currentLang] ?? '🌐'; ?></span>
                <?php endif; ?>
                <span><?php echo $languages[$currentLang] ?? 'Language'; ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="languageDropdown">
                <?php foreach ($languages as $code => $name): ?>
                    <li>
                        <a class="dropdown-item <?php echo $code === $currentLang ? 'active' : ''; ?>" 
                           href="?lang=<?php echo $code; ?><?php echo isset($_GET['lang']) ? '' : '&' . http_build_query(array_diff_key($_GET, ['lang' => ''])); ?>">
                            <?php if ($showFlags): ?>
                                <span class="me-2"><?php echo $flags[$code] ?? '🌐'; ?></span>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($name); ?>
                            <?php if ($code === $currentLang): ?>
                                <i class="fas fa-check ms-2 text-success"></i>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
    } else {
        // Botones inline
        $buttonClass = $navbarStyle ? 'btn btn-outline-light btn-sm' : 'btn btn-outline-secondary btn-sm';
        ?>
        <div class="btn-group" role="group" aria-label="Language selector">
            <?php foreach ($languages as $code => $name): ?>
                <a href="?lang=<?php echo $code; ?><?php echo isset($_GET['lang']) ? '' : '&' . http_build_query(array_diff_key($_GET, ['lang' => ''])); ?>" 
                   class="<?php echo $buttonClass; ?> <?php echo $code === $currentLang ? 'active' : ''; ?>"
                   title="<?php echo htmlspecialchars($name); ?>">
                    <?php if ($showFlags): ?>
                        <?php echo $flags[$code] ?? '🌐'; ?>
                    <?php else: ?>
                        <?php echo strtoupper($code); ?>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    return ob_get_clean();
}

function renderLanguageSelectorMini($currentLang = null) {
    return renderLanguageSelector($currentLang, true, false, false);
}

function renderLanguageSelectorNavbar($currentLang = null) {
    return renderLanguageSelector($currentLang, true, true, true);
}
