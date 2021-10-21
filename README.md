# Installation en local

## Récupération et installation du projet

D'abord, un petit ```git clone``` à partir d'[ici](https://github.com/O-clock-Trinity/projet-le-nexus-back).

A la racine du projet, ```composer install``` dans un terminal.

## Création du fichier d'environnement local

A la racine du projet, créer un fichier nommé ```.env.local```

Y coller cette ligne:
```DATABASE_URL="mysql://db_user:db_password@127.0.0.1:3306/db_name"```

Remplacer ```db_user``` et ```db_password``` par votre identifiant et mot de passe adminer.(créer si besoin un utilisateur dans votre adminer au préalable).

Remplacer ```db_name``` par le nom que vous donnerez à votre BDD.

## Le terminal, c'est génial

A la racine du projet, ouvrir un terminal.

Lancer successivement les commandes:

```bin/console d:d:c```

```bin/console d:m:m```

```bin/console lexik:jwt:generate-keypair```

le projet back est prêt, ne reste qu'à lancer un serveur de développement:

```php -S localhost:8000 -t public```

***Et Voilà!***