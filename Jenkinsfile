pipeline {
    agent any

    environment {
        GIT_REPO   = "https://github.com/RaniaAtr/EcoRania.git"
        GIT_BRANCH = "main"
        TEMP_DIR   = "${WORKSPACE}/ecoactivities"
        DEPLOY_DIR = "/var/www/html/ecoactivities"
    }

    stages {
        stage('Cloner le dépôt dans le dossier temporaire') {
            steps {
                echo "🔄 Suppression de l’ancien dossier temporaire et clonage..."
                sh "rm -rf ${TEMP_DIR}"
                sh "git clone -b ${GIT_BRANCH} ${GIT_REPO} ${TEMP_DIR}"
            }
        }
    }

    post {
        success {
            echo '✅ Clonage temporaire terminé avec succès !'
        }
        failure {
            echo '❌ Le clonage a échoué.'
        }
    }
}
