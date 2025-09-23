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

        stage('Installation des dépendances') {
            steps {
                dir("${TEMP_DIR}") {
                echo " Installation des dépendances PHP..."
                sh "${COMPOSER_BIN} install --no-interaction --optimize-autoloader"
        }
            }
        }
        stage('Configuration de l\'environnement') {
            steps {
                script {
                        def envLocal = """
                        APP_ENV=prod
                        APP_DEBUG=0
                        DATABASE_URL="mysql://eco_user:motdepassefort@127.0.0.1:3306/ecoactivitiesdb?serverVersion=8.0&charset=utf8mb4"
                        """.stripIndent()

                        writeFile file: "${TEMP_DIR}/.env.local", text: envLocal
                        echo "✅ Fichier .env.local créé avec succès"
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
