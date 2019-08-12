<?php namespace AliasProject\SEMRush;

use Silktide\SemRushApi\ClientFactory as SEMClient;
use Cache\Adapter\PHPArray\ArrayCachePool;
use Silktide\SemRushApi\Data\Column as SEMColumn;
use Log;
use Exception;

class SEMRush
{
    protected $client = null;
    protected $cache = null;
    protected $domainPaid = [];
    protected $regions = [
        'en-us' => \Silktide\SemRushApi\Data\Database::DATABASE_GOOGLE_US,
        'en-ca' => \Silktide\SemRushApi\Data\Database::DATABASE_GOOGLE_CA,
        'es-mx' => \Silktide\SemRushApi\Data\Database::DATABASE_GOOGLE_MX,
        'en-uk' => \Silktide\SemRushApi\Data\Database::DATABASE_GOOGLE_UK,
        'fr-fr' => \Silktide\SemRushApi\Data\Database::DATABASE_GOOGLE_FR,
        'it-it' => \Silktide\SemRushApi\Data\Database::DATABASE_GOOGLE_IT,
        'de-de' => \Silktide\SemRushApi\Data\Database::DATABASE_GOOGLE_DE,
        'es-es' => \Silktide\SemRushApi\Data\Database::DATABASE_GOOGLE_ES,
        'ga-ie' => \Silktide\SemRushApi\Data\Database::DATABASE_GOOGLE_IE,
        'ru-ru' => \Silktide\SemRushApi\Data\Database::DATABASE_GOOGLE_RU,
        'hi-in' => \Silktide\SemRushApi\Data\Database::DATABASE_GOOGLE_IN,
        'zh-hk-hk' => \Silktide\SemRushApi\Data\Database::DATABASE_GOOGLE_HK,
        'en-au' => \Silktide\SemRushApi\Data\Database::DATABASE_GOOGLE_AU,
        'nl-be' => \Silktide\SemRushApi\Data\Database::DATABASE_GOOGLE_BE,
        'pt-br' => \Silktide\SemRushApi\Data\Database::DATABASE_GOOGLE_BR
    ];

    public function __construct()
    {
        try {
            $this->client = SEMClient::create(config('semrush.api_key'));

            // Set Cache
            $cache = new ArrayCachePool();
            $this->client->setCache($cache);
            
            // Set Timeout
            $this->client->setTimeout(30);
            $this->client->setConnectTimeout(30);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return false;
        }
    }

    /**
     * Domain Overview
     *
     * @param string $url
     * @param string $region
     * @return array
     */
    public function getDomainOverview($url, $region="en-us")
    {
        $results = [];

        $domain_ranks = $this->client->getDomainRanks(
            $url,
            [
                'database' => $this->regions[$region],
                'export_columns' => [
                    SEMColumn::COLUMN_OVERVIEW_DATABASE,
                    SEMColumn::COLUMN_OVERVIEW_DOMAIN,
                    SEMColumn::COLUMN_OVERVIEW_SEMRUSH_RATING,
                    SEMColumn::COLUMN_OVERVIEW_ORGANIC_KEYWORDS,
                    SEMColumn::COLUMN_OVERVIEW_ORGANIC_TRAFFIC,
                    SEMColumn::COLUMN_OVERVIEW_ORGANIC_BUDGET,
                    SEMColumn::COLUMN_OVERVIEW_ADWORDS_KEYWORDS,
                    SEMColumn::COLUMN_OVERVIEW_ADWORDS_TRAFFIC,
                    SEMColumn::COLUMN_OVERVIEW_ADWORDS_BUDGET,
                    SEMColumn::COLUMN_OVERVIEW_PLA_UNIQUES,
                    SEMColumn::COLUMN_OVERVIEW_PLA_KEYWORDS
                ]
            ]
        );

        $results = [
            'database' => $domain_ranks[0]->getValue(SEMColumn::COLUMN_OVERVIEW_DATABASE),
            'domain' => $domain_ranks[0]->getValue(SEMColumn::COLUMN_OVERVIEW_DOMAIN),
            'rank' => (int) $domain_ranks[0]->getValue(SEMColumn::COLUMN_OVERVIEW_SEMRUSH_RATING),
            'organic_keywords' => (int) $domain_ranks[0]->getValue(SEMColumn::COLUMN_OVERVIEW_ORGANIC_KEYWORDS),
            'organic_traffic' => (int) $domain_ranks[0]->getValue(SEMColumn::COLUMN_OVERVIEW_ORGANIC_TRAFFIC),
            'organic_cost' => (int) $domain_ranks[0]->getValue(SEMColumn::COLUMN_OVERVIEW_ORGANIC_BUDGET),
            'adwords_keywords' => (int) $domain_ranks[0]->getValue(SEMColumn::COLUMN_OVERVIEW_ADWORDS_KEYWORDS),
            'adwords_traffic' => (int) $domain_ranks[0]->getValue(SEMColumn::COLUMN_OVERVIEW_ADWORDS_TRAFFIC),
            'adwords_cost' => (int) $domain_ranks[0]->getValue(SEMColumn::COLUMN_OVERVIEW_ADWORDS_BUDGET),
            'pla_uniques' => (int) $domain_ranks[0]->getValue(SEMColumn::COLUMN_OVERVIEW_PLA_UNIQUES),
            'pla_keywords' => (int) $domain_ranks[0]->getValue(SEMColumn::COLUMN_OVERVIEW_PLA_KEYWORDS)
        ];

        return $results;
    }

    /**
     * Domain Organic Search Keywords
     *
     * @param string $url
     * @param array $brands
     * @param int $limit
     * @param int $offset
     * @param string $region
     * @param string $filters
     * @return array
     */
    public function collectDomainOrganic(string $url, Array $brands, int $limit=5, int $offset=0, string $region="en-us", $filters=null)
    {
        try {
            $results = [];
            $brand_filters = '';

            $brand_counter = 0;
            foreach ($brands as $brand) {
                $brand_filters .= (!$brand_counter) ? '-|Ph|Co|'.strtolower($brand) : '|-|Ph|Co|'.strtolower($brand);
                $brand_counter++;
            }

            if ($filters) {
                $filter_counter = 0;
                foreach ($filters as $filter) {
                    if (!$filter['value']) continue;
                    $filter_string = $filter['sign'] . '|' . $filter['field'] . '|' . $filter['operator'] . '|' . $filter['value'];
                    $brand_filters .= '|' . $filter_string;
                }
            }

            // Set client timeout
            $this->client->setTimeout(30);

            $organic_results = $this->client->getDomainOrganic(
                $url,
                [
                    'database' => $this->regions[$region],
                    'display_limit' => $limit + $offset,
                    'display_offset' => $offset,
                    'export_columns' => [
                        SEMColumn::COLUMN_DOMAIN_KEYWORD,
                        SEMColumn::COLUMN_DOMAIN_KEYWORD_ORGANIC_POSITION,
                        SEMColumn::COLUMN_DOMAIN_KEYWORD_PREVIOUS_ORGANIC_POSITION,
                        SEMColumn::COLUMN_DOMAIN_KEYWORD_POSITION_DIFFERENCE,
                        SEMColumn::COLUMN_KEYWORD_AVERAGE_QUERIES,
                        SEMColumn::COLUMN_KEYWORD_AVERAGE_CLICK_PRICE,
                        SEMColumn::COLUMN_DOMAIN_KEYWORD_TARGET_URL,
                        SEMColumn::COLUMN_DOMAIN_KEYWORD_TRAFFIC_PERCENTAGE,
                        SEMColumn::COLUMN_KEYWORD_ESTIMATED_PRICE,
                        SEMColumn::COLUMN_KEYWORD_COMPETITIVE_AD_DENSITY,
                        SEMColumn::COLUMN_KEYWORD_ORGANIC_NUMBER_OF_RESULTS,
                        SEMColumn::COLUMN_KEYWORD_INTEREST
                    ],
                    'display_sort' => 'nq_desc',
                    'display_filter' => '-|Po|Gt|20|'.$brand_filters
                ]
            );

            foreach ($organic_results as $row) {
                $keyword = $row->getValue(SEMColumn::COLUMN_DOMAIN_KEYWORD);

                $results[$keyword] = [
                    'position' => (int) $row->getValue(SEMColumn::COLUMN_DOMAIN_KEYWORD_ORGANIC_POSITION),
                    'previous_position' => (int) $row->getValue(SEMColumn::COLUMN_DOMAIN_KEYWORD_PREVIOUS_ORGANIC_POSITION),
                    'position_difference' => (int) $row->getValue(SEMColumn::COLUMN_DOMAIN_KEYWORD_POSITION_DIFFERENCE),
                    'search_volume' => (int) $row->getValue(SEMColumn::COLUMN_KEYWORD_AVERAGE_QUERIES),
                    'cpc' => (float) $row->getValue(SEMColumn::COLUMN_KEYWORD_AVERAGE_CLICK_PRICE),
                    'url' => $row->getValue(SEMColumn::COLUMN_DOMAIN_KEYWORD_TARGET_URL),
                    'traffic' => (float) $row->getValue(SEMColumn::COLUMN_DOMAIN_KEYWORD_TRAFFIC_PERCENTAGE),
                    'traffic_cost' => (float) $row->getValue(SEMColumn::COLUMN_KEYWORD_ESTIMATED_PRICE),
                    'competition' => (float) $row->getValue(SEMColumn::COLUMN_KEYWORD_COMPETITIVE_AD_DENSITY),
                    'number_of_results' => (int) $row->getValue(SEMColumn::COLUMN_KEYWORD_ORGANIC_NUMBER_OF_RESULTS),
                    'trends' => $row->getValue(SEMColumn::COLUMN_KEYWORD_INTEREST)
                ];
            };

            return $results;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Domain Paid Search Keywords
     *
     * @param string $url
     * @param array $brands
     * @param int $limit
     * @param int $offset
     * @param string $region
     * @param string $filters
     * @return array
     */
    public function collectDomainPaid(string $url, Array $brands, $limit=5, $offset=0, $region="en-us", $filters=null)
    {
        try {
            $results = [];
            $brand_filters = '';

            $brand_counter = 0;
            foreach ($brands as $brand) {
                $brand_filters .= (!$brand_counter) ? '-|Ph|Co|'.strtolower($brand) : '|-|Ph|Co|'.strtolower($brand);
                $brand_counter++;
            }

            if ($filters) {
                $filter_counter = 0;
                foreach ($filters as $filter) {
                    if (!$filter['value']) continue;
                    $filter_string = $filter['sign'] . '|' . $filter['field'] . '|' . $filter['operator'] . '|' . $filter['value'];
                    $brand_filters .= '|' . $filter_string;
                }
            }

            $paid_results = $this->client->getDomainAdwords(
                $url,
                [
                    'database' => $this->regions[$region],
                    'display_limit' => $limit + $offset,
                    'display_offset' => $offset,
                    'export_columns' => [
                        SEMColumn::COLUMN_DOMAIN_KEYWORD,
                        SEMColumn::COLUMN_DOMAIN_KEYWORD_ORGANIC_POSITION,
                        SEMColumn::COLUMN_DOMAIN_KEYWORD_PREVIOUS_ORGANIC_POSITION,
                        SEMColumn::COLUMN_DOMAIN_KEYWORD_POSITION_DIFFERENCE,
                        SEMColumn::COLUMN_DOMAIN_ADWORD_POSITION,
                        SEMColumn::COLUMN_KEYWORD_AVERAGE_QUERIES,
                        SEMColumn::COLUMN_KEYWORD_AVERAGE_CLICK_PRICE,
                        SEMColumn::COLUMN_DOMAIN_KEYWORD_TRAFFIC_PERCENTAGE,
                        SEMColumn::COLUMN_KEYWORD_ESTIMATED_PRICE,
                        SEMColumn::COLUMN_KEYWORD_COMPETITIVE_AD_DENSITY,
                        SEMColumn::COLUMN_KEYWORD_ORGANIC_NUMBER_OF_RESULTS,
                        SEMColumn::COLUMN_KEYWORD_INTEREST,
                        SEMColumn::COLUMN_DOMAIN_KEYWORD_AD_TITLE,
                        SEMColumn::COLUMN_DOMAIN_KEYWORD_AD_TEXT,
                        SEMColumn::COLUMN_DOMAIN_KEYWORD_VISIBLE_URL,
                        SEMColumn::COLUMN_DOMAIN_KEYWORD_TARGET_URL
                    ],
                    'display_sort' => 'nq_desc',
                    'display_filter' => $brand_filters
                ]
            );

            $paid_data = [];
            foreach ($paid_results as $row) {
                $keyword = $row->getValue(SEMColumn::COLUMN_DOMAIN_KEYWORD);

                $results[$keyword] = [
                    'position' => (int) $row->getValue(SEMColumn::COLUMN_DOMAIN_KEYWORD_ORGANIC_POSITION),
                    'previous_position' => (int) $row->getValue(SEMColumn::COLUMN_DOMAIN_KEYWORD_PREVIOUS_ORGANIC_POSITION),
                    'position_difference' => $row->getValue(SEMColumn::COLUMN_DOMAIN_KEYWORD_POSITION_DIFFERENCE),
                    'adword_position' => $row->getValue(SEMColumn::COLUMN_DOMAIN_ADWORD_POSITION),
                    'search_volume' => (int) $row->getValue(SEMColumn::COLUMN_KEYWORD_AVERAGE_QUERIES),
                    'cpc' => (float) $row->getValue(SEMColumn::COLUMN_KEYWORD_AVERAGE_CLICK_PRICE),
                    'traffic' => (float) $row->getValue(SEMColumn::COLUMN_DOMAIN_KEYWORD_TRAFFIC_PERCENTAGE),
                    'traffic_cost' => (float) $row->getValue(SEMColumn::COLUMN_KEYWORD_ESTIMATED_PRICE),
                    'competition' => (float) $row->getValue(SEMColumn::COLUMN_KEYWORD_COMPETITIVE_AD_DENSITY),
                    'number_of_results' => (int) $row->getValue(SEMColumn::COLUMN_KEYWORD_ORGANIC_NUMBER_OF_RESULTS),
                    'trends' => $row->getValue(SEMColumn::COLUMN_KEYWORD_INTEREST),
                    'title' => $row->getValue(SEMColumn::COLUMN_DOMAIN_KEYWORD_AD_TITLE),
                    'description' => $row->getValue(SEMColumn::COLUMN_DOMAIN_KEYWORD_AD_TEXT),
                    'visible_url' => $row->getValue(SEMColumn::COLUMN_DOMAIN_KEYWORD_VISIBLE_URL),
                    'url' => $row->getValue(SEMColumn::COLUMN_DOMAIN_KEYWORD_TARGET_URL)
                ];
            }

            return $results;
        } catch (Exception $e) {
            Log::info($e->getMessage());

            return [];
        }
    }

    /**
     * Domain PLA Search Keywords
     *
     * @param string $url
     * @param array $brands
     * @param int $limit
     * @param int $offset
     * @param string $region
     * @param string $filters
     * @return array
     */
    public function collectDomainPlaSearchKeywords(string $url, Array $brands, $limit=5, $offset=0, $region="en-us", $filters=null)
    {
        try {
            $results = [];
            $brand_filters = '';

            $brand_counter = 0;
            foreach ($brands as $brand) {
                $brand_filters .= (!$brand_counter) ? '-|Ph|Co|'.strtolower($brand) : '|-|Ph|Co|'.strtolower($brand);
                $brand_counter++;
            }

            if ($filters) {
                $filter_counter = 0;
                foreach ($filters as $filter) {
                    if (!$filter['value']) continue;
                    $filter_string = $filter['sign'] . '|' . $filter['field'] . '|' . $filter['operator'] . '|' . $filter['value'];
                    $brand_filters .= '|' . $filter_string;
                }
            }

            $adhistory_results = $this->client->getDomainPlaSearchKeywords(
                $url,
                [
                    'database' => $this->regions[$region],
                    'display_limit' => $limit + $offset,
                    'display_offset' => $offset,
                    'export_columns' => [
                        SEMColumn::COLUMN_DOMAIN_KEYWORD,
                        SEMColumn::COLUMN_DOMAIN_KEYWORD_ORGANIC_POSITION,
                        SEMColumn::COLUMN_DOMAIN_KEYWORD_PREVIOUS_ORGANIC_POSITION,
                        SEMColumn::COLUMN_DOMAIN_KEYWORD_POSITION_DIFFERENCE,
                        SEMColumn::COLUMN_KEYWORD_AVERAGE_QUERIES,
                        SEMColumn::COLUMN_DOMAIN_KEYWORD_SHOP_NAME,
                        SEMColumn::COLUMN_DOMAIN_KEYWORD_TARGET_URL,
                        SEMColumn::COLUMN_DOMAIN_KEYWORD_AD_TITLE,
                        SEMColumn::COLUMN_DOMAIN_KEYWORD_PRODUCT_PRICE,
                        SEMColumn::COLUMN_TIMESTAMP
                    ],
                    'display_sort' => 'nq_desc',
                    'display_filter' => $brand_filters
                ]
            );

            foreach ($adhistory_results as $row) {
                $keyword = $row->getValue(SEMColumn::COLUMN_DOMAIN_KEYWORD);

                $results[$keyword] = [
                    'position' => (int) $row->getValue(SEMColumn::COLUMN_DOMAIN_KEYWORD_ORGANIC_POSITION),
                    'previous_position' => (int) $row->getValue(SEMColumn::COLUMN_DOMAIN_KEYWORD_PREVIOUS_ORGANIC_POSITION),
                    'position_difference' => (int) $row->getValue(SEMColumn::COLUMN_DOMAIN_KEYWORD_POSITION_DIFFERENCE),
                    'search_volume' => (int) $row->getValue(SEMColumn::COLUMN_KEYWORD_AVERAGE_QUERIES),
                    'shop_name' => $row->getValue(SEMColumn::COLUMN_DOMAIN_KEYWORD_SHOP_NAME),
                    'url' => $row->getValue(SEMColumn::COLUMN_DOMAIN_KEYWORD_TARGET_URL),
                    'title' => $row->getValue(SEMColumn::COLUMN_DOMAIN_KEYWORD_AD_TITLE),
                    'product_price' => (float) $row->getValue(SEMColumn::COLUMN_DOMAIN_KEYWORD_PRODUCT_PRICE),
                    'timestamp' => (int) $row->getValue(SEMColumn::COLUMN_TIMESTAMP)
                ];
            };

            return $results;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Keyword Difficulty
     *
     * @param string $phrase
     * @param string $region
     * @return array
     */
    public function collectKeywordDifficulty($phrase, $region="en-us")
    {
        $results = [];
        
        // Get Difficulty
        $keyword_results = $this->client->getKeywordDifficulty(
            $phrase,
            [
                'database' => $this->regions[$region],
                'export_columns' => [
                    SEMColumn::COLUMN_DOMAIN_KEYWORD,
                    SEMColumn::COLUMN_KEYWORD_DIFFICULTY_INDEX
                ]
            ]
        );

        foreach ($keyword_results as $row) {
            $keyword = $row->getValue(SEMColumn::COLUMN_DOMAIN_KEYWORD);
            $keyword_difficulty = $row->getValue(SEMColumn::COLUMN_KEYWORD_DIFFICULTY_INDEX);

            $results[$keyword] = $keyword_difficulty;
        };

        return $results;
    }

    /**
     * Backlinks Overview
     *
     * @param string $domain
     * @param string $target_type
     * @return array
     */
    public function getBacklinksOverview(string $domain, string $target_type="domain")
    {
        $data = [];
        $results = $this->client->getBacklinksOverview(
            $domain,
            [
                'target_type' => $target_type,
                'export_columns' => [
                    SEMColumn::COLUMN_TOTAL,
                    SEMColumn::COLUMN_DOMAINS_NUM,
                    SEMColumn::COLUMN_URLS_NUM,
                    SEMColumn::COLUMN_IPS_NUM,
                    SEMColumn::COLUMN_IPCLASSC_NUM,
                    SEMColumn::COLUMN_TEXTS_NUM,
                    SEMColumn::COLUMN_FOLLOWS_NUM,
                    SEMColumn::COLUMN_FORMS_NUM,
                    SEMColumn::COLUMN_NOFOLLOWS_NUM,
                    SEMColumn::COLUMN_FRAMES_NUM,
                    SEMColumn::COLUMN_IMAGES_NUM,
                    SEMColumn::COLUMN_SCORE,
                    SEMColumn::COLUMN_TRUST_SCORE
                ]
            ]
        );

        $data = [
            'total' => (int) $results[0]->getValue(SEMColumn::COLUMN_TOTAL),
            'domains_num' => (int) $results[0]->getValue(SEMColumn::COLUMN_DOMAINS_NUM),
            'urls_num' => (int) $results[0]->getValue(SEMColumn::COLUMN_URLS_NUM),
            'ips_num' => (int) $results[0]->getValue(SEMColumn::COLUMN_IPS_NUM),
            'ipclassc_num' => (int) $results[0]->getValue(SEMColumn::COLUMN_IPCLASSC_NUM),
            'texts_num' => (int) $results[0]->getValue(SEMColumn::COLUMN_TEXTS_NUM),
            'follows_num' => (int) $results[0]->getValue(SEMColumn::COLUMN_FOLLOWS_NUM),
            'forms_num' => (int) $results[0]->getValue(SEMColumn::COLUMN_FORMS_NUM),
            'nofollows_num' => (int) $results[0]->getValue(SEMColumn::COLUMN_NOFOLLOWS_NUM),
            'frames_num' => (int) $results[0]->getValue(SEMColumn::COLUMN_FRAMES_NUM),
            'images_num' => (int) $results[0]->getValue(SEMColumn::COLUMN_IMAGES_NUM),
            'score' => (int) $results[0]->getValue(SEMColumn::COLUMN_SCORE),
            'trust_score' => (int) $results[0]->getValue(SEMColumn::COLUMN_TRUST_SCORE)
        ];

        return $data;
    }

    /**
     * Backlinks
     *
     * @param string $domain
     * @param string $target_type
     * @param int $limit
     * @param int $offset
     * @param string $filter
     * @return array
     */
    public function getBacklinks(string $domain, string $target_type="domain", int $limit=5, int $offset=0, string $filter="")
    {
        $data = [];
        $results = $this->client->getBacklinks(
            $domain,
            [
                'target_type' => $target_type,
                'display_limit' => $limit + $offset,
                'display_offset' => $offset,
                'display_filter' => $filter,
                'export_columns' => [
                    SEMColumn::COLUMN_PAGE_SCORE,
                    SEMColumn::COLUMN_PAGE_TRUST_SCORE,
                    SEMColumn::COLUMN_RESPONSE_CODE,
                    SEMColumn::COLUMN_SOURCE_SIZE,
                    SEMColumn::COLUMN_EXTERNAL_NUM,
                    SEMColumn::COLUMN_INTERNAL_NUM,
                    SEMColumn::COLUMN_REDIRECT_URL,
                    SEMColumn::COLUMN_SOURCE_URL,
                    SEMColumn::COLUMN_SOURCE_TITLE,
                    SEMColumn::COLUMN_IMAGE_URL,
                    SEMColumn::COLUMN_TARGET_URL,
                    SEMColumn::COLUMN_TARGET_TITLE,
                    SEMColumn::COLUMN_ANCHOR,
                    SEMColumn::COLUMN_IMAGE_ALT,
                    SEMColumn::COLUMN_ADVERTISER_AD_LAST_SEEN,
                    SEMColumn::COLUMN_ADVERTISER_AD_FIRST_SEEN,
                    SEMColumn::COLUMN_NOFOLLOW,
                    SEMColumn::COLUMN_FORM,
                    SEMColumn::COLUMN_FRAME,
                    SEMColumn::COLUMN_IMAGE,
                    SEMColumn::COLUMN_SITEWIDE,
                    SEMColumn::COLUMN_NEWLINK,
                    SEMColumn::COLUMN_LOSTLINK
                ]
            ]
        );

        foreach ($results as $row) {
            $data[] = [
                'page_score' => $row->getValue(SEMColumn::COLUMN_PAGE_SCORE),
                'page_trust_score' => $row->getValue(SEMColumn::COLUMN_PAGE_TRUST_SCORE),
                'response_code' => $row->getValue(SEMColumn::COLUMN_RESPONSE_CODE),
                'source_size' => $row->getValue(SEMColumn::COLUMN_SOURCE_SIZE),
                'external_num' => $row->getValue(SEMColumn::COLUMN_EXTERNAL_NUM),
                'internal_num' => $row->getValue(SEMColumn::COLUMN_INTERNAL_NUM),
                'redirect_url' => $row->getValue(SEMColumn::COLUMN_REDIRECT_URL),
                'source_url' => $row->getValue(SEMColumn::COLUMN_SOURCE_URL),
                'source_title' => $row->getValue(SEMColumn::COLUMN_SOURCE_TITLE),
                'image_url' => $row->getValue(SEMColumn::COLUMN_IMAGE_URL),
                'target_url' => $row->getValue(SEMColumn::COLUMN_TARGET_URL),
                'target_title' => $row->getValue(SEMColumn::COLUMN_TARGET_TITLE),
                'anchor' => $row->getValue(SEMColumn::COLUMN_ANCHOR),
                'image_alt' => $row->getValue(SEMColumn::COLUMN_IMAGE_ALT),
                'last_seen' => $row->getValue(SEMColumn::COLUMN_ADVERTISER_AD_LAST_SEEN),
                'first_seen' => $row->getValue(SEMColumn::COLUMN_ADVERTISER_AD_FIRST_SEEN),
                'nofollow' => $row->getValue(SEMColumn::COLUMN_NOFOLLOW),
                'form' => $row->getValue(SEMColumn::COLUMN_FORM),
                'frame' => $row->getValue(SEMColumn::COLUMN_FRAME),
                'image' => $row->getValue(SEMColumn::COLUMN_IMAGE),
                'sitewide' => $row->getValue(SEMColumn::COLUMN_SITEWIDE),
                'newlink' => $row->getValue(SEMColumn::COLUMN_NEWLINK),
                'lostlink' => $row->getValue(SEMColumn::COLUMN_LOSTLINK)
            ];
        }

        return $data;
    }

    /**
     * Referring Domains
     *
     * @param string $domain
     * @param string $target_type
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getReferringDomains(string $domain, string $target_type="domain", int $limit=5, int $offset=0)
    {
        $data = [];
        $results = $this->client->getBacklinksReferringDomains(
            $domain,
            [
                'target_type' => $target_type,
                'display_limit' => $limit + $offset,
                'display_offset' => $offset,
                'export_columns' => [
                    SEMColumn::COLUMN_DOMAIN_SCORE,
                    SEMColumn::COLUMN_DOMAIN_TRUST_SCORE,
                    SEMColumn::COLUMN_ADVERTISER_AD_DOMAIN,
                    SEMColumn::COLUMN_BACKLINKS_NUM,
                    SEMColumn::COLUMN_IP,
                    SEMColumn::COLUMN_COUNTRY,
                    SEMColumn::COLUMN_ADVERTISER_AD_FIRST_SEEN,
                    SEMColumn::COLUMN_ADVERTISER_AD_LAST_SEEN
                ]
            ]
        );

        foreach ($results as $row) {
            $data[] = [
                'domain_score' => $row->getValue(SEMColumn::COLUMN_DOMAIN_SCORE),
                'domain_trust_score' => $row->getValue(SEMColumn::COLUMN_DOMAIN_TRUST_SCORE),
                'domain' => $row->getValue(SEMColumn::COLUMN_ADVERTISER_AD_DOMAIN),
                'backlinks_num' => $row->getValue(SEMColumn::COLUMN_BACKLINKS_NUM),
                'ip' => $row->getValue(SEMColumn::COLUMN_IP),
                'country' => $row->getValue(SEMColumn::COLUMN_COUNTRY),
                'first_seen' => $row->getValue(SEMColumn::COLUMN_ADVERTISER_AD_FIRST_SEEN),
                'last_seen' => $row->getValue(SEMColumn::COLUMN_ADVERTISER_AD_LAST_SEEN)
            ];
        }

        return $data;
    }

    /**
     * Referring IPs
     *
     * @param string $domain
     * @param string $target_type
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getReferringIPs(string $domain, string $target_type="domain", int $limit=5, int $offset=0)
    {
        $data = [];
        $results = $this->client->getBacklinksReferringIPs(
            $domain,
            [
                'target_type' => $target_type,
                'display_limit' => $limit + $offset,
                'display_offset' => $offset,
                'export_columns' => [
                    SEMColumn::COLUMN_IP,
                    SEMColumn::COLUMN_COUNTRY,
                    SEMColumn::COLUMN_DOMAINS_NUM,
                    SEMColumn::COLUMN_BACKLINKS_NUM,
                    SEMColumn::COLUMN_ADVERTISER_AD_FIRST_SEEN,
                    SEMColumn::COLUMN_ADVERTISER_AD_LAST_SEEN
                ]
            ]
        );

        foreach ($results as $row) {
            $data[] = [
                'ip' => $row->getValue(SEMColumn::COLUMN_IP),
                'country' => $row->getValue(SEMColumn::COLUMN_COUNTRY),
                'domains_num' => $row->getValue(SEMColumn::COLUMN_DOMAINS_NUM),
                'backlinks_num' => $row->getValue(SEMColumn::COLUMN_BACKLINKS_NUM),
                'first_seen' => $row->getValue(SEMColumn::COLUMN_ADVERTISER_AD_FIRST_SEEN),
                'last_seen' => $row->getValue(SEMColumn::COLUMN_ADVERTISER_AD_LAST_SEEN)
            ];
        }

        return $data;
    }

    /**
     * Indexed Pages
     *
     * @param string $domain
     * @param string $target_type
     * @param int $limit
     * @return array
     */
    public function getIndexedPages(string $domain, string $target_type="domain", int $limit=5, string $sort="domains_num_desc")
    {
        $data = [];
        $results = $this->client->getBacklinksIndexedPages(
            $domain,
            [
                'target_type' => $target_type,
                'display_limit' => $limit,
                'display_sort' => $sort,
                'export_columns' => [
                    SEMColumn::COLUMN_RESPONSE_CODE,
                    SEMColumn::COLUMN_BACKLINKS_NUM,
                    SEMColumn::COLUMN_DOMAINS_NUM,
                    SEMColumn::COLUMN_ADVERTISER_AD_LAST_SEEN,
                    SEMColumn::COLUMN_EXTERNAL_NUM,
                    SEMColumn::COLUMN_INTERNAL_NUM,
                    SEMColumn::COLUMN_SOURCE_URL,
                    SEMColumn::COLUMN_SOURCE_TITLE
                ]
            ]
        );

        foreach ($results as $row) {
            $data[] = [
                'response_code' => $row->getValue(SEMColumn::COLUMN_RESPONSE_CODE),
                'backlinks_num' => $row->getValue(SEMColumn::COLUMN_BACKLINKS_NUM),
                'domains_num' => $row->getValue(SEMColumn::COLUMN_DOMAINS_NUM),
                'last_seen' => $row->getValue(SEMColumn::COLUMN_ADVERTISER_AD_LAST_SEEN),
                'external_num' => $row->getValue(SEMColumn::COLUMN_EXTERNAL_NUM),
                'internal_num' => $row->getValue(SEMColumn::COLUMN_INTERNAL_NUM),
                'source_url' => $row->getValue(SEMColumn::COLUMN_SOURCE_URL),
                'source_title' => $row->getValue(SEMColumn::COLUMN_SOURCE_TITLE)
            ];
        }

        return $data;
    }
}
