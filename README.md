# Recrute Stagiaire — thème Shopify

Thème Liquid du site Recrute Stagiaire : vitrine, offres, profils et dépôt de CV.

Le navigateur n'appelle pas Supabase directement. Le thème passe par le proxy PHP décrit dans `ionos/README.md`, hébergé à part. C'est ce script qui détient la clé serveur.

## Déployer le thème

Dans l'admin Shopify : Ventes en ligne, Ajouter un thème, Se connecter depuis GitHub, dépôt `recrutestagiaire`, branche `main`. Un push sur `main` met à jour le thème lié.

Les changements se font dans ce dépôt. Une édition dans l'éditeur de thème Shopify n'est pas dans Git.

## Structure

`layout/`, `templates/`, `sections/`, `snippets/`, `assets/`, `locales/`, `config/`, et `ionos/` pour le proxy.
