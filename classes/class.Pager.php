<?php
/**
 * Class Pager
 * Modified by Keyvan and James.
 *
 * @author Tsigo <tsigo@tsiris.com>
 * @version 1.0
 * @package paginated-comments-pager
 */
class Paginated_Comments_Pager
{
	/**
	 * Items per page.
	 *
	 * This is used, along with <var>$item_total</var>, to calculate how many
	 * pages are needed.
	 * @var int
	 */
	var $items_per_page;

	/**
	 * Total number of items
	 *
	 * This is used, along with <var>$items_per_page</var>, to calculate how many
	 * pages are needed.
	 * @var int
	 */
	var $item_total;

	/**
	 * Current page
	 * @var int
	 */
	var $current_page;

	/**
	 * Number of pages needed
	 * @var int
	 */
	var $num_pages;

	/**
	 * Constructor
	 */
	function Paginated_Comments_Pager($items_per_page, $item_total)
	{
		$this->items_per_page = $items_per_page;
		$this->item_total = $item_total;
		$this->num_pages = (int) ceil($this->item_total / $this->items_per_page);
		$this->set_current_page(1);
	}

	/**
	 * Set current page number
	 * @param int $page
	 */
	function set_current_page($page)
	{
		$this->current_page = min($page, $this->num_pages());
		$this->current_page = max($this->current_page, 1);
	}

	/**
	 * Get current page
	 * @return int
	 */
	function get_current_page()
	{
		return $this->current_page;
	}

	/**
	 * Get items per page
	 * @return int
	 */
	function get_items_per_page()
	{
		return $this->items_per_page;
	}

	/**
	 * Get total items
	 * @return int
	 */
	function get_total_items()
	{
		return $this->item_total;
	}

	/**
	 * Number of pages needed
	 * @return int
	 */
	function num_pages() 
	{
		return $this->num_pages;
	}

	/**
	 * Is last page
	 * @return boolean
	 */
	function is_last_page()
	{
		return ($this->get_current_page() == $this->num_pages());
	}

	/**
	 * Is first page
	 * @return boolean
	 */
	function is_first_page()
	{
		return ($this->get_current_page() == 1);
	}

	/**
	 * Get page numbers within range
	 * @param int $page_range number of pages to display at one time, default: all pages
	 * @return array
	 */
	function get_page_numbers($page_range=null)
	{
		if ( !isset($page_range) ) {
			return range(1, $this->num_pages());
		} else {
			// set boundaries
			$pages = $this->num_pages();
			$range_halved = (int) floor($page_range / 2);
			$count_start = $this->current_page - $range_halved;
			$count_end = $this->current_page + $range_halved;

			// adjust boundaries
			while ( $count_start < 1 ) {
				$count_start++;
				$count_end++;
			}
			while ( $count_end > $pages ) {
				$count_end--;
				$count_start--;
			}
			$count_start = max($count_start, 1);
			return range($count_start, $count_end);
		}
	}
}

	/*
	 * Implements the Pager interface but inverts numbers. (Decorator pattern)
	 */
class Paginated_Comments_InvertedPager
{
	/**
	 * Pager Object Reference
	 * @var object
	 */
	var $pager;

	/**
	 * Constructor
	 */
	function Paginated_Comments_InvertedPager(&$pager)
	{
		$this->pager =& $pager;
	}

	/**
	 * Invert page order
	 * @param int $page
	 */
	function _invert_page($page)
	{
		return $this->pager->num_pages() + 1 - $page;
	}

	/**
	 * Set current page number
	 * @param int $page
	 */
	function set_current_page($page)
	{
		$this->pager->set_current_page($this->_invert_page($page));
	}

	/**
	 * Get current page
	 * @return int
	 */
	function get_current_page()
	{
		return $this->_invert_page($this->pager->get_current_page());
	}

	/**
	 * Get page numbers within range
	 * @param int $page_range number of pages to display at one time, default: all pages
	 * @return array
	 */
	function get_page_numbers($page_range=null)
	{
		return array_map(array(&$this, '_invert_page'), $this->pager->get_page_numbers($page_range));
	}

	/**
	 * Get items per page
	 * @return int
	 */
	function get_items_per_page()
	{
		return $this->pager->get_items_per_page();
	}

	/**
	* Get total items
	* @return int
	*/
	function get_total_items()
	{
	return $this->pager->get_total_items();
	}

	/**
	* Number of pages needed
	* @return int
	*/
	function num_pages() 
	{
		return $this->pager->num_pages();
	}

	/**
	 * Is last page
	 * @return boolean
	 */
	function is_last_page()
	{
		return ($this->get_current_page() == $this->num_pages());
	}

	/**
	 * Is first page
	 * @return boolean
	 */
	function is_first_page()
	{
		return ($this->get_current_page() == 1);
	}
}

	/**
	 * Prints page number links using a Pager instance
	 */
class Paginated_Comments_PagePrinter
{
	/**
	 * Pager Object Reference
	 * @var object
	 */
	var $pager;

	/**
	 * URL formatting string for building page links
	 *
	 * This should be a formatting string which will be passed to sprintf()
	 * (see: <http://uk.php.net/sprintf>), it should include 1 conversion
	 * specification: %u (to hold the page number)
	 * @var string
	 */
	var $url;

	/**
	 * Number of pages to show at one time
	 * @var int
	 */
	var $page_range;

	/**
	 * Constructor
	 */
	function Paginated_Comments_PagePrinter(&$pager, $url='', $page_range=null)
	{
		$this->pager =& $pager;
		$this->set_page_range($page_range);
		$this->set_url($url);
	}

	/**
	 * Generate previous link
	 * @param string $text Text to link
	 * @param string $title title attribute for the link
	 * @return string
	 */
	function get_prev_link($text='&laquo;', $title='Previous Page')
	{
		if ( $this->pager->is_first_page() ) return '';
		return '<a href="'.$this->get_url($this->pager->get_current_page() - 1).'" title="'.$title.'">'.$text.'</a>';
	}

	/**
	 * Generate next link
	 * @param string $text Text to link
	 * @param string $title title attribute for the link
	 * @return string
	 */
	function get_next_link($text='&raquo;', $title='Next Page')
	{
		if ( $this->pager->is_last_page() ) return '';
		return '<a href="'.$this->get_url($this->pager->get_current_page() + 1).'" title="'.$title.'">'.$text.'</a>';
	}

	/**
	 * Get page links
	 * @return string HTML
	 */
	function get_links($separator=' ', $pre_cur_page='<strong>[', $post_cur_page=']</strong>')
	{
		$pages = $this->pager->num_pages();
		$page_links= '';

		// print page numbers
		$cur_page = $this->pager->get_current_page();
		$num_links = array();
		$page_numbers = $this->pager->get_page_numbers($this->page_range);
		$asc = ($page_numbers[0] < $page_numbers[1]);
		if ( $asc ) {
			if( $page_numbers[0] != 1 )
				$num_links[] = '<a href="'.$this->get_url(1)."\">1</a> &#8230;";
		} else {
			if( $page_numbers[0] != $this->pager->num_pages() )
				$num_links[] = '<a href="'.$this->get_url($this->pager->num_pages())."\">".$this->pager->num_pages()."</a> &#8230;";
		}
		foreach ( $page_numbers as $i) {
			if ( $i == $cur_page )
				$num_links[] = $pre_cur_page.$i.$post_cur_page;
			else
				$num_links[] = '<a href="'.$this->get_url($i)."\">$i</a>";
		}
		if( $asc ) {
			if( $page_numbers[count($page_numbers)-1] != $this->pager->num_pages() )
				$num_links[] = '&#8230; <a href="'.$this->get_url($this->pager->num_pages())."\">".$this->pager->num_pages()."</a>";
		} else {
			if( $page_numbers[count($page_numbers)-1] != 1 )
				$num_links[] = '&#8230; <a href="'.$this->get_url(1)."\">1</a>";
		}
		$page_links .= implode($separator, $num_links);
		return $page_links;
	}

	/**
	 * Set page range
	 * @param int $max
	 */
	function set_page_range($max)
	{
		$this->page_range = $max;
	}

	/**
	 * Set URL
	 * @param string $url
	 */
	function set_url($url)
	{
		$this->url = $url;
	}

	/**
	 * Get formatted URL (including page number)
	 * @param int $page page number
	 * @return string
	 */
	function get_url($page)
	{
		return sprintf($this->url, $page);
	}
}
?>