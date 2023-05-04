<?php 

include_once('phpQuery-onefile.php');


class ReviewGrabber 
{
    private string $url = '';
    private array $data = [];

    public function __construct(string $url)
    {
        $this->url = $url;
    }

    private function getRatingValue(string $value) : int
    {
        $value = trim($value);
        if (strlen($value) > 0) {
            $pieces = explode(' ', $value);
            if (count($pieces) >= 2) {
                return (int)$pieces[1];   
            } 
        }

        return 0;
    }

    private function getFormattedDate(string $date) : string
    {
        $date = trim($date);
        if (strlen($date > 0)) {
            $date = mb_strimwidth($date, 0, 10);
            $pieces = explode(".", $date);
            if (count($pieces) >= 3) {
                return $pieces[2].'-'.$pieces[1].'-'.$pieces[0];
            }
        }

        return $date;
    }

    private function getFormattedReview(string $review) : string
    { 
        $text = str_replace(
            array('\r\n', '\r', '\n', '\n\r'),
            ' ',
            strip_tags($review)
        );
        return trim($text);
    }

    public function getReviewData() : array 
    {
        if (strlen($this->url) > 0) {
            $pq = PhpQuery::newDocumentFile($this->url);
            if ($this->hasReviewOnPage($pq)) {
                $this->getReviewFromHTML($pq);

                $pageNumber = $this->getReviewPagesNumber($pq);
                if ($pageNumber > 0) {
                    for ($i = 1; $i < $pageNumber; $i++) {
                        $step = 20 * $i; 
                        $pq = PhpQuery::newDocumentFile($this->url.'?pagestart='.$step);
                        $this->getReviewFromHTML($pq);
                    }
                }
            }
        }

        return $this->data;
    }

    private function hasReviewOnPage(phpQueryObject $pq) : bool
    {
        $text = $pq->find('.feed')->text();
        if (strlen($text) > 0) {
            return true;
        }

        return false;
    }

    private function getReviewPagesNumber(phpQueryObject $pq) : int
    {
        $text = $pq->find('.page-of-pages')->html(); 
        if (strlen($text) > 0) {
            $pieces = explode(' ', $text);
            return (int)$pieces[3];
        }       

        return 0;
    }

    private function getReviewFromHTML(phpQueryObject $pq) 
    {
        $htmlReview = $pq->find('.review--with-sidebar');
        foreach ($htmlReview as $itemReview) {
            $el = pq($itemReview);
            
            // get review author
            $author = $el->find('.user-display-name')->text();
            if (strlen($author) > 0) {
                // get review rating
                $rating = $el->find('.i-stars');
                $rating = $this->getRatingValue($rating->attr('title'));
    
                // get review date
                $date = $el->find('.rating-qualifier')->text();     
                $date = $this->getFormattedDate($date);
            
                // get review text
                $review = $el->find('.review-content p')->text();
                $review = $this->getFormattedReview($review);
            
                $reviewItem = array(
                    'author' => $author, 
                    'date' => $date, 
                    'rating' => $rating, 
                    'text' => $review
                );
                array_push($this->data, $reviewItem);

                // это класс div если у пользователя несколько отзывов
                $htmlPreviousReview = $el->find('.previous-review'); 
                foreach ($htmlPreviousReview as $itemPreviousReview) {
                    $el = pq($itemPreviousReview); 
                    // get previous review rating
                    $rating = $el->find('.i-stars');
                    $rating = $this->getRatingValue($rating->attr('title'));
            
                    // get previous review date 
                    $date = $el->find('.rating-qualifier')->text(); 
                    $date = $this->getFormattedDate($date);
    
                    // get previous review text
                    $review = $el->find('.js-expandable-comment span:eq(0)')->text(); 
                    $review = $this->getFormattedReview($review);

                    $reviewItem = array(
                        'author' => $author, 
                        'date' => $date, 
                        'rating' => $rating, 
                        'text' => $review
                    );
                    array_push($this->data, $reviewItem);
                }
            }
        }   
    }
}


?>