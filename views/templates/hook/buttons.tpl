{*
 * APLINE Simple PDF Instructions module for PrestaShop 9.
 * @author APLINE Arkadiusz Pielechowski
 *}
{if $buttons|@count}
  <div class="apline-simple-pdf-instructions">
    {foreach from=$buttons item=button}
      <a href="{$button.url|escape:'html':'UTF-8'}"
         class="aspd-button"
         style="background:{$button.color|escape:'html':'UTF-8'};"
         target="_blank"
         rel="noopener noreferrer">
        {if $button.icon_left}
          <span class="aspd-icon aspd-icon-entity">{$button.icon_left nofilter}</span>
        {/if}
        {if $button.label}
          <span class="aspd-label">{$button.label|escape:'html':'UTF-8'}</span>
        {/if}
        {if $button.icon_right}
          <span class="aspd-icon aspd-icon-entity">{$button.icon_right nofilter}</span>
        {/if}
      </a>
    {/foreach}
  </div>
{/if}
