pipeline {
    agent any
    environment {
        // SonarQube
        projectName = "Jarvis-project"
        scannerHome = tool 'sonarscanner'
        // Nexus
        image_name_base = "stage.acoba.com/web-service"
    }

    stages {
        // Stage to set permissions for all scripts
        stage('Set Permissions') {
            steps {
                sh '''
                    chmod +x jenkins/scripts/*.sh
                '''
            }
        }

        // Stages for dev environment
        stage('UNIT TEST') {
            when {
                branch 'dev*'
            }
            steps {
                sh './jenkins/scripts/unit-tests.sh'
            }
        }

        stage('SEND REPORT') {
            when {
                branch 'dev*'
            }
            steps {
                script {
                    sh 'xsltproc attendancemonitoring/tests/phpunit-report.xsl test-results.xml test-results.html'
                    def emailBody = readFile('test-results.html')
                    emailext(
                        to: 'ahmed.aminzaag@acoba.com',
                        subject: "PHP Unit Test Report: ${env.projectName} - Build #${env.BUILD_NUMBER}",
                        body: emailBody,
                        mimeType: 'text/html'
                    )
                }
            }
        }

        stage('CODE ANALYSIS with SONARQUBE') {
            when {
                branch 'dev*'
            }
            environment {
                PATH = "${scannerHome}/bin:${env.PATH}"
            }
            steps {
                withSonarQubeEnv('sonarserver') {
                    sh """
                        ${scannerHome}/bin/sonar-scanner \
                        -Dsonar.projectKey=${projectName} \
                        -Dsonar.sources=. \
                        -Dsonar.host.url=${env.SONAR_HOST_URL} \
                        -Dsonar.login=${env.SONAR_AUTH_TOKEN} \
                        -Dsonar.projectName=${projectName} \
                        -Dsonar.projectVersion=${env.BUILD_ID}
                    """
                }
            }
        }

        stage('DEPLOY TO DEV ENVIRONMENT') {
            when {
                branch 'dev*'
            }
            steps {
                withCredentials([[
                    $class: 'AmazonWebServicesCredentialsBinding',
                    accessKeyVariable: 'AWS_ACCESS_KEY_ID',
                    secretKeyVariable: 'AWS_SECRET_ACCESS_KEY',
                    credentialsId: 'Jenkins-With-Beanstalk-Credentials'
                ]]) {
                    sh './jenkins/scripts/deploy-to-dev.sh'
                }
            }
        }

        // Stages for beta environment
        stage('DEPLOY TO BETA ENVIRONMENT') {
            when {
                branch 'beta*'
            }
            steps {
                withCredentials([[
                    $class: 'AmazonWebServicesCredentialsBinding',
                    accessKeyVariable: 'AWS_ACCESS_KEY_ID',
                    secretKeyVariable: 'AWS_SECRET_ACCESS_KEY',
                    credentialsId: 'Jenkins-With-Beanstalk-Credentials'
                ]]) {
                    sh './jenkins/scripts/deploy-to-beta.sh'
                }
            }
        }

        stage('LOAD TESTS') {
            steps {
                echo 'Executing Load Tests ....'
                sleep time: 30, unit: 'SECONDS'
            }
        }

        // Stages for production environment
        stage('Building Image') {
            when {
                branch 'main'
            }
            steps {
                echo 'Building Image ...'
                sh "docker build -t ${image_name_base}:${env.BUILD_NUMBER} -t ${image_name_base}:latest ."
            }
        }
        stage('Pushing Image to Nexus') {
            when {
                branch 'main'
            }
            steps {
                echo 'Pushing image to docker hosted repository on Nexus ...'
                withCredentials([usernamePassword(credentialsId: 'nexus', passwordVariable: 'PSW', usernameVariable: 'USER')]) {
                    sh "echo ${PSW} | docker login -u ${USER} --password-stdin stage.acoba.com/repo"
                    sh "docker push ${image_name_base}:${env.BUILD_NUMBER}"
                    sh "docker push ${image_name_base}:latest"
                }
            }
        }

        stage('Modify Docker Compose') {
            when {
                branch 'main'
            }
            steps {
                script {
                    sh './jenkins/scripts/modify-docker-compose.sh'
                }
            }
        }        
        stage('DEPLOY TO PROD ENVIRONMENT') {
            when {
                branch 'main'
            }
            steps {
                withCredentials([[
                    $class: 'AmazonWebServicesCredentialsBinding',
                    accessKeyVariable: 'AWS_ACCESS_KEY_ID',
                    secretKeyVariable: 'AWS_SECRET_ACCESS_KEY',
                    credentialsId: 'Jenkins-With-Beanstalk-Credentials'
                ]]) {
                    sh './jenkins/scripts/deploy-to-prod.sh'
                }
            }
        }
    }
}
