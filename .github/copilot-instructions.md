# .github/copilot-instructions.md

## Usage de RTK

Quand tu proposes ou analyses des commandes terminal, utilise RTK pour réduire le bruit et les tokens.

Préférer :

- `rtk git status`
- `rtk git diff`
- `rtk composer install`
- `rtk composer test`
- `rtk vendor/bin/phpunit`
- `rtk npm run build`
- `rtk docker compose logs`

Ne pas utiliser RTK pour les commandes interactives ou très courtes sans sortie utile.

Inutile de produire une documentation .md, sauf si demande explicite
