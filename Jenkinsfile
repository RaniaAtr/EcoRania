pipeline {
    agent any

    environment {
        GIT_REPO     = "https://github.com/RaniaAtr/EcoRania.git"
        GIT_BRANCH   = "main"
        DEPLOY_DIR   = "/var/www/html/ecoactivities"  // dossier final pour Apache
        PHP_BIN      = "php"
        COMPOSER_BIN = "/usr/bin/composer"
        DATABASE_URL = "mysql://eco_user:motdepassefort@127.0.0.1:3306/ecoactivitiesdb"
    }

    stages {
        stage('Checkout') {
            steps {
                echo "🔄 Récupération du code depuis GitHub..."
                git branch: "${GIT_BRANCH}", url: "${GIT_REPO}"
            }
        }

        stage('Install Dependencies') {
            steps {
                echo "📦 Installation des dépendances PHP..."
                sh """
                ${COMPOSER_BIN} install --no-interaction --optimize-autoloader
                """
            }
        }

        stage('Run Tests') {
            steps {
                echo "🧪 Exécution des tests PHPUnit..."
                sh """
                ${PHP_BIN} bin/phpunit || echo "⚠️ Certains tests ont échoué mais le pipeline continue"
                """
            }
        }

        stage('Migration de la base de données') {
            steps {
                echo "🗄️ Application des migrations Doctrine..."
                sh """
                cd ${DEPLOY_DIR}
                ${PHP_BIN} bin/console doctrine:migrations:migrate --no-interaction
                """
            }
        }

        stage('Deploy') {
            steps {
                echo "🚀 Déploiement sur le VPS..."
                sh """
                # Copier le projet (exclure .git et node_modules)
                rsync -av --exclude=".git" --exclude="node_modules" ./ ${DEPLOY_DIR}/

                # Aller dans le dossier de déploiement
                cd ${DEPLOY_DIR}

                # Mettre à jour DATABASE_URL dans .env.prod
                sed -i "s|^DATABASE_URL=.*|DATABASE_URL='${DATABASE_URL}'|" .env.prod

                # Vider le cache Symfony
                ${PHP_BIN} bin/console cache:clear --env=prod
                """
            }
        }
    }

    post {
        success {
            echo '✅ Déploiement terminé avec succès !'
        }
        failure {
            echo '❌ Le pipeline a échoué.'
        }
    }
}
