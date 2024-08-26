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
            when {
                anyOf {
                    branch 'dev'
                    branch 'beta'
                    branch 'main'
                }
            }
            steps {
                sh '''
                    chmod +x jenkins/scripts/*.sh
                '''
            }
        }

        // Stages for dev environment
        stage('UNIT TEST') {
            when {
                branch 'dev'
            }
            steps {
                sh 'phpunit --log-junit test-results.xml attendancemonitoring/tests/unitTest.php'
            }
        }

        stage('SEND REPORT') {
    when {
        branch 'dev'
    }
    steps {
        script {
            // Ensure the XML report exists
            if (fileExists('test-results.xml')) { 
                echo "XML report found: test-results.xml"

                // Transform the XML report to HTML
                sh 'xsltproc attendancemonitoring/tests/phpunit-report.xsl test-results.xml > test-results.html'

                // Check if the HTML report was created successfully
                if (fileExists('test-results.html')) {
                    echo "HTML report generated: test-results.html"

                    // Read the HTML report and send it via email
                    def emailBody = readFile('test-results.html')
                    emailext(
                        to: 'ahmed.aminzaag@acoba.com',
                        subject: "PHP Unit Test Report: ${env.projectName} - Build #${env.BUILD_NUMBER}",
                        body: emailBody,
                        mimeType: 'text/html'
                    )
                } else {
                    echo "Error: HTML report was not generated."
                }
            } else {
                echo "Error: XML report not found: test-results.xml"
            }
        }
    }
    }

        stage('CODE ANALYSIS with SONARQUBE') {
            when {
                branch 'dev'
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
                branch 'dev'
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
                branch 'beta'
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

        stage('Run JMeter Test') {
            when {
                branch 'beta'
            }
            steps {
                script {
                    def jmeterPath = '/opt/apache-jmeter-5.6.3/bin'
                    def testPlanPath = "${jmeterPath}/jmeter-senarios/s1.jmx"
                    def resultsPath = "${env.WORKSPACE}/results.csv" // Ensure correct file format

                    // Run the JMeter test plan with CSV output format
                    sh "${jmeterPath}/jmeter -n -t ${testPlanPath} -l ${resultsPath} -Jjmeter.save.saveservice.output_format=csv"
                }
            }
        }
        stage('Generate Report') {
            when {
                branch 'beta'
            }
            steps {
                script {
                    def jmeterPath = '/opt/apache-jmeter-5.6.3/bin'
                    def resultsPath = "${env.WORKSPACE}/results.csv"
                    def reportPath = "${env.WORKSPACE}/jmeter-report"
                    
                    // Create the report directory
                    sh "mkdir -p ${reportPath}"
                    
                    // Generate the HTML report
                    sh "${jmeterPath}/jmeter -g ${resultsPath} -o ${reportPath}"
                    
                    // Archive the report
                    archiveArtifacts artifacts: "jmeter-report/**", allowEmptyArchive: true
                    
                    // Publish performance report with comparison to a past build
                    perfReport sourceDataFiles: 'results.csv', 
                              filterRegex: '', 
                              relativeFailedThresholdNegative: 1.2, 
                              relativeFailedThresholdPositive: 1.89, 
                              relativeUnstableThresholdNegative: 1.8, 
                              relativeUnstableThresholdPositive: 1.5, 
                              modeEvaluation: true, 
                              nthBuildNumber: 1
                }
            }
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

    post {
        always {
            cleanWs()
        }
    }
}
