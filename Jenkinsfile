pipeline {
    agent any

    environment {
        GIT_REPO   = "https://github.com/RaniaAtr/EcoRania.git"
        GIT_BRANCH = "main"
        TEMP_DIR   = "${WORKSPACE}/ecoactivities"
        DEPLOY_DIR = "/var/www/html/ecoactivities"
        COMPOSER_BIN = "composer"
    }

    stages {
        stage('Cloner le dépôt dans le dossier temporaire') {
            steps {
                echo "🔄 Suppression de l’ancien dossier temporaire et clonage..."
                sh "rm -rf ${TEMP_DIR}"
                sh "git clone -b ${GIT_BRANCH} ${GIT_REPO} ${TEMP_DIR}"
            }
        }

        stage('Configuration de l\'environnement') {
            steps {
                script {
                def envFile = """
                APP_ENV=prod
                APP_DEBUG=0
                DATABASE_URL="mysql://sonar:sonar@127.0.0.1:3306/ecobase?serverVersion=11&charset=utf8"
                MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0
            """.stripIndent()

                // Écrire à la fois .env et .env.local
                writeFile file: "${TEMP_DIR}/.env", text: envFile
                writeFile file: "${TEMP_DIR}/.env.local", text: envFile
                echo "✅ Fichiers .env et .env.local créés avec succès"
                }
                }
        }

        stage('Installation des dépendances') {
            steps {
                dir("${TEMP_DIR}") {
                echo " Installation des dépendances PHP..."
                sh "${COMPOSER_BIN} install --no-interaction --optimize-autoloader"
        }
            }
        }
        stage('Vérifier PHP') {
            steps {
                sh 'which php'
                sh 'php -v'
                sh 'php -m | grep pdo_mysql'
                }
        }
        stage('Migration de la base de données') {
            steps {
                dir("${TEMP_DIR}") {
                echo " Exécution des migrations Doctrine..."
                // Applique les migrations pour mettre à jour la base de données
                sh "php bin/console doctrine:database:create --if-not-exists"
                sh "php bin/console doctrine:migrations:migrate --no-interaction"
        }
    }
}
        
        
    }

    post {
        success {
            echo 'Étapes temporaires terminées avec succès'
        }
        failure {
            echo 'Le pipeline a échoué'
        }
    }
}
