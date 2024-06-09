pipeline {
    agent any
    environment {
        //sonarqube
        projectName = "Jarvis-project"
        scannerHome = tool 'sonarscanner'
        //nexus
        image_name_base = "stage.acoba.com/web-service"
    }
    stages {
        stage('checkout SCM Git') {
            steps {
                checkout scmGit(branches: [[name: '*/main']], extensions: [], userRemoteConfigs: [[url: 'https://github.com/aminos98/jarvis-backend', credentialsId: 'github-token']])
            }
        }
        stage('UNIT TEST') {
            steps {
                sh 'phpunit --log-junit test-results.xml attendancemonitoring/tests/unitTest.php'
            }
        }
        stage('Transform and Send Report') {
            steps {
                script {
                    sh 'xsltproc attendancemonitoring/tests/phpunit-report.xsl test-results.xml > test-results.html'
                    def emailBody = readFile('test-results.html')
                    emailext(
                        to: 'ahmed.aminzaag@acoba.com',
                        subject: "PHP Unit Test Report: ${env.projectName} - Build # ${env.BUILD_NUMBER}",
                        body: emailBody,
                        mimeType: 'text/html'
                    )
                }
            }
        }
        stage('CODE ANALYSIS with SONARQUBE') {
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
        stage('Building Image') {
            steps {
                echo 'Building Image ...'
                sh "docker build -t ${image_name_base}:${env.BUILD_NUMBER} -t ${image_name_base}:latest ."
            }
        }
        stage('Pushing Image to Nexus') {
            steps {
                echo 'Pushing image to docker hosted repository on Nexus ...'
                withCredentials([usernamePassword(credentialsId: 'nexus', passwordVariable: 'PSW', usernameVariable: 'USER')]) {
                    sh "echo ${PSW} | docker login -u ${USER} --password-stdin stage.acoba.com/repo"
                    sh "docker push ${image_name_base}:${env.BUILD_NUMBER}"
                    sh "docker push ${image_name_base}:latest"
                }
            }
        }
        stage('Deploy to AWS') {
            steps {
                withCredentials([[
                    $class: 'AmazonWebServicesCredentialsBinding',
                    accessKeyVariable: 'AWS_ACCESS_KEY_ID',
                    secretKeyVariable: 'AWS_SECRET_ACCESS_KEY',
                    credentialsId: 'Jenkins-With-Beanstalk-Credentials'
                ]]) {
                    sh "echo 'n' | eb init --region eu-west-1 ws-jarvis-dev-docker"
                    sh "eb deploy"
                }
            }
        }
    }
}
