pipeline {
    agent any

    environment {
        GIT_REPO     = "https://github.com/RaniaAtr/EcoRania.git"
        GIT_BRANCH   = "main"
        TEMP_DIR     = "${WORKSPACE}/ecoactivities"
        DEPLOY_DIR   = "/var/www/html/ecoactivities"
        COMPOSER_BIN = "composer"
        SSH_OPTS     = "-o StrictHostKeyChecking=no"
        REMOTE_USER  = "ubuntu"
        REMOTE_HOST  = "51.75.207.28"
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
                        DATABASE_URL="mysql://sonar:sonar@51.75.207.28:3306/ecobase?serverVersion=11&charset=utf8"
                        MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0
                    """.stripIndent()

                    writeFile file: "${TEMP_DIR}/.env", text: envFile
                    writeFile file: "${TEMP_DIR}/.env.local", text: envFile
                    echo "✅ Fichiers .env et .env.local créés avec succès"
                }
            }
        }

        stage('Installation des dépendances') {
            steps {
                dir("${TEMP_DIR}") {
                    echo "📦 Installation des dépendances PHP..."
                    sh "${COMPOSER_BIN} install --no-interaction --optimize-autoloader"
                }
            }
        }

        stage('Vérifier PHP') {
            steps {
                sh 'which php'
                sh 'php -v'
                sh 'php -m | grep pdo_mysql || true'
            }
        }

        stage('Migrations DB (non-prod)') {
            when { expression { return env.CLEAN_DB == 'true' } }
            steps {
                dir("${TEMP_DIR}") {
                    sh '''
                        set -e
                        php bin/console doctrine:database:drop --if-exists --force
                        php bin/console doctrine:database:create --if-not-exists
                        php bin/console doctrine:migrations:sync-metadata-storage --no-interaction
                        php bin/console doctrine:migrations:migrate --no-interaction
                    '''
    }
  }
}

        stage('Exécution des tests PHP Unit') {
            steps {
                dir("${TEMP_DIR}") {
                    echo " Lancement des tests PHPUnit..."
                    sh './vendor/bin/phpunit --testdox'
                }
            }
        }

        stage('Nettoyage du cache') {
            steps {
                dir("${TEMP_DIR}") {
                    echo "🧹 Nettoyage et réchauffage du cache Symfony..."
                    sh 'php bin/console cache:clear --env=prod'
                    sh 'php bin/console cache:warmup'
                }
            }
        }

        stage('Déploiement') {
            steps {
                echo "🚀 Déploiement du site en production..."
                sh """
                    # 1. Vider le dossier distant
                    ssh ${SSH_OPTS} ${REMOTE_USER}@${REMOTE_HOST} "rm -rf ${DEPLOY_DIR}/*"

                    # 2. Copier le nouveau contenu
                    scp ${SSH_OPTS} -r "${TEMP_DIR}/" ${REMOTE_USER}@${REMOTE_HOST}:${DEPLOY_DIR}/
                """
            }
        }
    }

    post {
        success {
            echo '✅ Déploiement réussi !'
        }
        failure {
            echo '❌ Erreur lors du déploiement.'
        }
    }
}
