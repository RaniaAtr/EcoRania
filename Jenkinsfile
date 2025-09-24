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
                DATABASE_URL="mysql://sonar:sonar@172.18.0.1:3306/ecobase?serverVersion=11&charset=utf8"
                MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0
            """.stripIndent()

                // Écrire à la fois .env et .env.local
                writeFile file: "${TEMP_DIR}/.env", text: envFile
                writeFile file: "${TEMP_DIR}/.env.local", text: envFile
                echo " Fichiers .env et .env.local créés avec succès"
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
                sh "php bin/console doctrine:migrations:sync-metadata-storage"
                sh "php bin/console doctrine:migrations:migrate --no-interaction"
        }
    }
        }
        stage('Exécution des tests PHP unit') {
            steps{
                dir("${TEMP_DIR}") {
                    sh './vendor/bin/phpunit --testdox'
                }

            }
        }
        stage ('Nettoyer la cache'){
            steps{ dir("${TEMP_DIR}") {
                    echo " Nettoyage et réchauffage du cache Symfony..."
                    sh 'php bin/console cache:clear --env=prod'
                    sh 'php bin/console cache:warmup'
                }

            }
        }
        stage('Déploiement') {
            steps {
                echo "Déploiement du site en production..."
        
                // Supprime l'ancien dossier de déploiement
                sh "rm -rf /var/www/html/ecoactivities"
        
                // Crée le dossier de déploiement
                sh "mkdir -p /var/www/html/ecoactivities"
        
                // Copie tout le contenu du dossier temporaire vers le dossier de déploiement
                sh "cp -rT ${WORKSPACE}/ecoactivities /var/www/html/ecoactivities"
        
                // Ajuste les permissions pour que le serveur web puisse écrire dans var/cache et var/log
                sh "chmod -R 775 /var/www/html/ecoactivities/var"
            }
        }
  
    }

    post {
        success {
            echo 'Déploiement réussi !'
        }
        failure {
            echo 'Erreur lors du déploiement.'
        }
    }
}
