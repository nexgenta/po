<?php

uses('rdf');

abstract class ProgrammesOntology
{
	const po = 'http://purl.org/ontology/po/';

	public static function rdfInstance($namespaceURI, $localName)
	{
		switch($localName)
		{
		case 'Episode':
			return new POEpisode();
		case 'Series':
			return new POSeries();
		case 'Brand':
			return new POBrand();
		}
	}

	public static function instanceFromDocument($doc)
	{
		$g = $doc->primaryTopic();
		if(!$g)
		{
			return null;
		}
		if($g instanceof POThing)
		{
			$s = $g->subject();
			if($g instanceof POEpisode || $g instanceof POSeries)
			{
				foreach($doc->graphs as $graph)
				{
					if(!isset($g->brand) && $graph instanceof POBrand)
					{
						if($graph->hasEpisode($s) || $graph->hasSeries($s))
						{
							$g->brand = $graph->subject();
						}
					}
					else if(!isset($g->series) && $g instanceof POEpisode && $graph instanceof POSeries)
					{
						if($graph->hasEpisode($s))
						{
							$g->series = $graph->subject();
						}
					}
					if(isset($g->brand) && isset($g->series))
					{
						break;
					}
				}
			}
			return $g;
		}
		return null;
	}
}

RDF::$ontologies[ProgrammesOntology::po] = 'ProgrammesOntology';

class POThing extends RDFGraph
{
	public $pid;
	public $title;
	public $shortSynopsis;
	public $mediumSynopsis;
	public $longSynopsis;
	public $depiction;
	public $microsite;

	public function transform()
	{
		parent::transform();
		$this->pid = $this->first(ProgrammesOntology::po . 'pid');
		$this->title = $this->first(XMLNS::dc . 'title');
		$this->shortSynopsis = $this->first(ProgrammesOntology::po . 'short_synopsis');
		$this->longSynopsis = $this->first(ProgrammesOntology::po . 'long_synopsis');
		$this->mediumSynopsis = $this->first(ProgrammesOntology::po . 'medium_synopsis');
		$this->depiction = $this->first(RDF::foaf . 'depiction');
		$this->microsite = $this->first(ProgrammesOntology::po . 'microsite');
	}
}

class POEpisode extends POThing
{
	public $versions = array();
	public $clips = array();
	public $brand = null;
	public $series = null;

	public function transform()
	{
		parent::transform();
		if(isset($this->{ProgrammesOntology::po . 'version'}))
		{
			foreach($this->{ProgrammesOntology::po . 'version'} as $ver)
			{
				$this->versions[] = $ver;
			}
		}
		if(isset($this->{ProgrammesOntology::po . 'clip'}))
		{
			foreach($this->{ProgrammesOntology::po . 'clip'} as $clip)
			{
				$this->clips[] = $clip;
			}
		}
	}
}

class POSeries extends POThing
{
	public $episodes = array();
	public $clips = array();
	public $brand = null;

	public function transform()
	{
		parent::transform();
		if(isset($this->{ProgrammesOntology::po . 'episode'}))
		{
			foreach($this->{ProgrammesOntology::po . 'episode'} as $ep)
			{
				$this->episodes[] = $ep;
			}
		}
		if(isset($this->{ProgrammesOntology::po . 'clip'}))
		{
			foreach($this->{ProgrammesOntology::po . 'clip'} as $clip)
			{
				$this->clips[] = $clip;
			}
		}
	}
	
	public function hasEpisode($uri)
	{
		foreach($this->episodes as $ep)
		{
			if(!strcmp($uri, $ep))
			{
				return true;
			}
		}
		return false;
	}
}

class POBrand extends POSeries
{
	public $series = array();
	
	public function transform()
	{
		parent::transform();
		if(isset($this->{ProgrammesOntology::po . 'series'}))
		{
			foreach($this->{ProgrammesOntology::po . 'series'} as $series)
			{
				$this->series[] = $series;
			}
		}
	}

	public function hasSeries($uri)
	{
		foreach($this->series as $series)
		{
			if(!strcmp($uri, $series))
			{
				return true;
			}
		}
		return false;
	}
}
