<?php

namespace App\Services;

/**
 * Detects known skill keywords in job text (title + description) using a
 * curated dictionary — no external API. Also matches a job's detected skills
 * against a candidate's own skill list so results can show what overlaps.
 */
class SkillExtractor
{
    /**
     * Canonical skill label => list of text patterns that indicate it.
     * Patterns are matched case-insensitively as whole "words" (punctuation
     * like . # + is preserved so "Node.js", "C++", "C#" match correctly).
     */
    private const SKILLS = [
        // Languages
        'PHP' => ['php'], 'JavaScript' => ['javascript', 'js'], 'TypeScript' => ['typescript', 'ts'],
        'Python' => ['python'], 'Java' => ['java'], 'C++' => ['c++'], 'C#' => ['c#'], 'C' => ['c'],
        'Go' => ['golang', 'go'], 'Ruby' => ['ruby'], 'Swift' => ['swift'], 'Kotlin' => ['kotlin'],
        'Rust' => ['rust'], 'Dart' => ['dart'], 'R' => ['r'], 'Scala' => ['scala'], 'Perl' => ['perl'],
        'HTML' => ['html', 'html5'], 'CSS' => ['css', 'css3'], 'SQL' => ['sql'],

        // Frameworks / libraries
        'Laravel' => ['laravel'], 'Symfony' => ['symfony'], 'CodeIgniter' => ['codeigniter'],
        'React' => ['react', 'reactjs', 'react.js'], 'Vue.js' => ['vue', 'vuejs', 'vue.js'],
        'Angular' => ['angular', 'angularjs'], 'Next.js' => ['next.js', 'nextjs'],
        'Node.js' => ['node.js', 'nodejs', 'node'], 'Express.js' => ['express.js', 'expressjs', 'express'],
        'Django' => ['django'], 'Flask' => ['flask'], 'FastAPI' => ['fastapi'],
        'Spring Boot' => ['spring boot', 'springboot', 'spring'], '.NET' => ['.net', 'dotnet', 'asp.net'],
        'Ruby on Rails' => ['ruby on rails', 'rails'], 'WordPress' => ['wordpress'],
        'jQuery' => ['jquery'], 'Bootstrap' => ['bootstrap'], 'Tailwind CSS' => ['tailwind'],
        'Flutter' => ['flutter'], 'React Native' => ['react native'], 'Ionic' => ['ionic'],

        // Data / databases
        'MySQL' => ['mysql'], 'PostgreSQL' => ['postgresql', 'postgres'], 'MongoDB' => ['mongodb', 'mongo'],
        'Redis' => ['redis'], 'SQLite' => ['sqlite'], 'Oracle DB' => ['oracle db', 'oracle'],
        'Elasticsearch' => ['elasticsearch', 'elastic search'], 'Firebase' => ['firebase'],

        // Cloud / DevOps
        'AWS' => ['aws', 'amazon web services'], 'Azure' => ['azure'], 'Google Cloud' => ['gcp', 'google cloud'],
        'Docker' => ['docker'], 'Kubernetes' => ['kubernetes', 'k8s'], 'CI/CD' => ['ci/cd', 'ci-cd'],
        'Jenkins' => ['jenkins'], 'Terraform' => ['terraform'], 'Linux' => ['linux'], 'Nginx' => ['nginx'],
        'Git' => ['git'], 'GitHub' => ['github'], 'GitLab' => ['gitlab'], 'Bitbucket' => ['bitbucket'],

        // APIs / architecture
        'REST API' => ['rest api', 'restful', 'rest'], 'GraphQL' => ['graphql'], 'Microservices' => ['microservices'],
        'WebSockets' => ['websocket', 'websockets'], 'OOP' => ['oop', 'object-oriented', 'object oriented'],
        'MVC' => ['mvc'], 'Agile' => ['agile'], 'Scrum' => ['scrum'], 'Jira' => ['jira'],

        // Data / AI
        'Machine Learning' => ['machine learning', 'ml'], 'Deep Learning' => ['deep learning'],
        'Data Analysis' => ['data analysis', 'data analytics'], 'Pandas' => ['pandas'], 'NumPy' => ['numpy'],
        'TensorFlow' => ['tensorflow'], 'PyTorch' => ['pytorch'], 'Power BI' => ['power bi'], 'Tableau' => ['tableau'],
        'Excel' => ['excel', 'ms excel'],

        // Design / other
        'Figma' => ['figma'], 'Adobe XD' => ['adobe xd'], 'Photoshop' => ['photoshop'], 'UI/UX' => ['ui/ux', 'ui ux'],
        'SEO' => ['seo'], 'WooCommerce' => ['woocommerce'], 'Shopify' => ['shopify'], 'Magento' => ['magento'],

        // Soft / business
        'Communication' => ['communication skills', 'communication'], 'Leadership' => ['leadership'],
        'Team Management' => ['team management'], 'Project Management' => ['project management'],
        'Sales' => ['sales'], 'Marketing' => ['marketing'], 'Customer Support' => ['customer support', 'customer service'],
        'Accounting' => ['accounting'], 'HR' => ['human resources', 'hr'],
    ];

    /**
     * @return string[] canonical skill labels found in the text, in dictionary order.
     */
    public function extract(string $text): array
    {
        $hay = ' ' . mb_strtolower(strip_tags($text)) . ' ';
        $found = [];

        foreach (self::SKILLS as $label => $patterns) {
            foreach ($patterns as $p) {
                // Word-boundary-ish match: treat any non-alphanumeric char (incl.
                // our own punctuation like + # .) as a boundary.
                if (preg_match('/(?<![a-z0-9])' . preg_quote($p, '/') . '(?![a-z0-9])/i', $hay)) {
                    $found[] = $label;
                    break;
                }
            }
        }

        return $found;
    }

    /**
     * Split a free-text candidate skill list (comma/newline separated) into
     * clean, deduped entries.
     *
     * @return string[]
     */
    public function parseCandidateSkills(?string $raw): array
    {
        if (!$raw) return [];

        return array_values(array_unique(array_filter(array_map(
            'trim',
            preg_split('/[,\n]+/', $raw)
        ))));
    }

    /**
     * Compare a job's detected skills against the candidate's own skills.
     *
     * @param  string[]  $jobSkills
     * @param  string[]  $candidateSkills
     * @return array{matched: string[], other: string[]}
     */
    public function matchAgainst(array $jobSkills, array $candidateSkills): array
    {
        $wanted = array_map(fn ($s) => mb_strtolower(trim($s)), $candidateSkills);

        $matched = [];
        $other = [];
        foreach ($jobSkills as $skill) {
            if (in_array(mb_strtolower($skill), $wanted, true)) {
                $matched[] = $skill;
            } else {
                $other[] = $skill;
            }
        }

        return ['matched' => $matched, 'other' => $other];
    }
}
