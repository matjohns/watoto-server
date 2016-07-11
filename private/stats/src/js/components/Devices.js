import _ from 'lodash'
import Moment from 'moment'

import React from 'react'
import Section from 'grommet/components/Section'
import Heading from 'grommet/components/Heading'
import Tiles from 'grommet/components/Tiles'
import Tile from 'grommet/components/Tile'
import Table from 'grommet/components/Table'
import Chart from 'grommet/components/Chart'
import Meter from 'grommet/components/Meter'

import Data from '../util/Data'

function logScale(v) {
  return Math.log(Math.sqrt(v))
}

const Devices = React.createClass({
  render() {
    if (!this.props.data) return null

    return (
      <Section>
        <Heading tag='h2' align='center'>
          Devices
        </Heading>
        <Tiles fill={true}>
          <Tile>
            <Meter series={
              _.reduce(Data.groupOthers(this.props.data.devices.manufacturer, 9), (res, v, k) => {
                res.push({
                  label: _.startCase(k),
                  value: v,
                  colorIndex: (k == 'others' ? 'graph-13' : null),
                })
                return res
              }, [])
            } max={this.props.data.users.total} type='circle' stacked={true} legend={true} />
          </Tile>
          <Tile>
            <Meter series={
              _.reduce(this.props.data.devices.types, (res, v, k) => {
                res.push({
                  label: _.startCase(_.lowerCase(k)),
                  value: v,
                })
                return res
              }, [])
            } max={this.props.data.users.total} type='circle' stacked={true} legend={true} />
          </Tile>
        </Tiles>
        <Tiles fill={true}>
          <Tile>
            <Chart type='bar' series={[
              {
                values: _.reduce(this.props.data.devices.screens, (res, v, k) => {
                  var bits = _.split(k, 'x', 2)
                  var x = logScale(bits[0] * bits[1])
                  res.push([x, v])
                  return res
                }, []),
              },
            ]} xAxis={{
              placement: 'bottom',
              data: _.reduce(this.props.data.devices.screens, (res, v, k) => {
                var bits = _.split(k, 'x', 2)
                var x = logScale(bits[0] * bits[1])
                res.push({
                  label: k,
                  value: x,
                })
                return res
              }, []),
            }} important={
              _.findIndex(_.toPairs(this.props.data.devices.screens), (x) => {
                return x[0] == _.maxBy(_.toPairs(this.props.data.devices.screens), (xx) => xx[1])[0]
              })
            } units='devices' legend={{}} />
          </Tile>
        </Tiles>
        <Tiles fill={true}>
          <Tile>
            <Chart type='bar' series={[
              {
                values: _.reduce(this.props.data.devices.aspects, (res, v, k) => {
                  res.push([Math.floor(parseFloat(k) * 100), v])
                  return res
                }, []),
              },
            ]} xAxis={{
              placement: 'bottom',
              data: [
                {label: '16/9', value: 178},
                {label: '5/3', value: 167},
                {label: '8/5', value: 160},
                {label: '3/2', value: 150},
                {label: '4/3', value: 133},
              ],
            }} important={
              _.findIndex(_.toPairs(this.props.data.devices.aspects), (x) => {
                return x[0] == _.maxBy(_.toPairs(this.props.data.devices.aspects), (xx) => xx[1])[0]
              })
            } units='devices' legend={{}} />
          </Tile>
        </Tiles>
        <Tiles fill={true}>
          <Tile>
            <Meter series={
              _.reduce(this.props.data.devices.scales, (res, v, k) => {
                res.push({
                  label: k + 'x',
                  value: v,
                })
                return res
              }, [])
            } max={this.props.data.users.total} type='circle' stacked={true} legend={true} />
          </Tile>
        </Tiles>
      </Section>
    )
  }
})

module.exports = Devices
